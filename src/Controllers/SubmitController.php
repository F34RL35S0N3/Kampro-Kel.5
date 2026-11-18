<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Response;
use App\Core\View;
use App\Repositories\LaporanRepository;
use App\Services\FlowService;
use App\Services\LimitService;
use App\Services\ReportService;
use App\Support\Logger;
use App\Validation\SubmitValidator;

final class SubmitController
{
    public function submit(): void
    {
        $wizard = FlowService::touch();
        if ($wizard === null) {
            Response::redirect('/langkah/1');
        }

        if (!Csrf::verify(is_string($_POST['_csrf'] ?? null) ? $_POST['_csrf'] : null)) {
            Logger::warning('CSRF_FAIL', ['endpoint' => 'kirim']);
            http_response_code(419);
            View::render('errors/419', ['pageTitle' => 'Sesi kedaluwarsa'], 'layout_public');
            return;
        }

        if (trim((string) ($_POST['_hp_website'] ?? '')) !== '') {
            Logger::warning('HONEYPOT_HIT', ['endpoint' => 'kirim']);
            Response::redirect('/langkah/5');
        }

        if ((int) $wizard['flow_done'] < 4) {
            Logger::warning('FLOW_VIOLATION', ['endpoint' => 'kirim', 'flow_done' => (int) $wizard['flow_done']]);
            Response::redirect('/langkah/' . FlowService::currentStep($wizard));
        }

        // Idempotency key harus cocok dengan yang diterbitkan sesi.
        $postedIdem = is_string($_POST['idem_key'] ?? null) ? (string) $_POST['idem_key'] : '';
        $sessionIdem = is_string($wizard['idem_key'] ?? null) ? (string) $wizard['idem_key'] : '';
        if ($postedIdem === '' || $sessionIdem === '' || !hash_equals($sessionIdem, $postedIdem)) {
            Logger::warning('IDEM_KEY_MISMATCH');
            $this->backToReview('Sesi pengiriman tidak valid. Silakan tinjau ulang lalu kirim kembali.');
            return;
        }

        // AA-06 — token langkah 5
        $flowCheck = FlowService::verifyFlow($wizard, 5, is_string($_POST['_flow'] ?? null) ? $_POST['_flow'] : null);
        if (!$flowCheck['ok']) {
            Logger::warning('FLOW_TOKEN_INVALID', ['step' => 5, 'reason' => $flowCheck['reason']]);
            $this->backToReview('Sesi formulir kedaluwarsa. Silakan tinjau ulang lalu kirim kembali.');
            return;
        }

        // AA-05 — total waktu minimal sejak langkah 1
        $minTotal = (int) config('limits.min_total_seconds', 60);
        $elapsed = FlowService::totalElapsed($wizard);
        if ($elapsed < $minTotal) {
            $wait = $minTotal - $elapsed;
            Logger::warning('TOO_FAST', ['elapsed' => $elapsed]);
            http_response_code(429);
            header('Retry-After: ' . $wait);
            View::render('errors/429', [
                'pageTitle' => 'Mohon tunggu sebentar',
                'retry'     => $wait,
                'message'   => 'Pengiriman terlalu cepat. Mohon periksa kembali laporan Anda.',
                'backUrl'   => '/langkah/5',
            ], 'layout_public');
            return;
        }

        $block = LimitService::validationBlockSeconds();
        if ($block > 0) {
            http_response_code(429);
            header('Retry-After: ' . $block);
            View::render('errors/429', ['pageTitle' => 'Terlalu banyak percobaan', 'retry' => $block], 'layout_public');
            return;
        }

        // Persetujuan pernyataan kebenaran data
        if (($_POST['agree'] ?? '') !== '1') {
            $this->backToReview('Anda wajib menyetujui pernyataan kebenaran data sebelum mengirim.');
            return;
        }

        // V2.2.1 — kunci parameter tak dikenal
        $allowed = ['_csrf', '_flow', '_hp_website', 'agree', 'idem_key'];
        $unknown = array_diff(array_keys($_POST), $allowed);
        if ($unknown !== []) {
            Logger::warning('UNKNOWN_PARAMETERS', ['endpoint' => 'kirim', 'keys' => array_values($unknown)]);
            $this->backToReview('Permintaan tidak valid.');
            return;
        }

        // CX-08 — validasi ulang total semua langkah
        $state = FlowService::allData($wizard);
        $result = SubmitValidator::validateAll($state);
        if ($result['step'] > 0) {
            LimitService::registerValidationFailure(\App\Core\Session::id());
            Logger::event('VALIDATION_FAIL', ['step' => $result['step'], 'fields' => array_keys($result['errors'])]);
            $_SESSION['_step_result'] = [
                'step'   => $result['step'],
                'errors' => $result['errors'],
                'old'    => [],
            ];
            Response::redirect('/langkah/' . $result['step']);
        }

        $ipHash = ip_hash(request_ip(), (string) config('app.hmac_key'));

        try {
            $outcome = ReportService::submit($result['data'], $postedIdem, $ipHash);
        } catch (\Throwable $e) {
            Logger::error('SUBMIT_FAILED', ['type' => $e::class, 'message' => $e->getMessage()]);
            http_response_code(500);
            View::render('errors/500', ['pageTitle' => 'Gagal menyimpan laporan'], 'layout_public');
            return;
        }

        if ($outcome['ok'] && in_array($outcome['code'], ['created', 'idempotent'], true)) {
            $regNo = (string) $outcome['reg_no'];
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            $_SESSION['submissions'][$regNo] = time();
            FlowService::reset();

            Logger::event('LAPORAN_SUBMITTED', ['reg_no' => $regNo]);
            Response::redirect('/sukses/' . rawurlencode($regNo));
        }

        match ($outcome['code']) {
            'ip'   => $this->tooMany((int) ($outcome['retry'] ?? 3600), 'Batas pengiriman per jaringan tercapai.'),
            'nip'  => $this->tooMany(86400, 'Batas pengiriman untuk NIP ini tercapai dalam 24 jam.'),
            'global' => $this->quotaExceeded(),
            'duplicate' => $this->backToReview('Laporan dengan isi identik sudah pernah dikirim.'),
            default => $this->backToReview('Laporan tidak dapat dikirim saat ini. Silakan coba lagi.'),
        };
    }

    public function success(array $params): void
    {
        $regNo = trim((string) ($params['reg'] ?? ''));
        $allowed = $_SESSION['submissions'] ?? [];

        if ($regNo === '' || !isset($allowed[$regNo])) {
            http_response_code(403);
            View::render('errors/403', ['pageTitle' => 'Akses ditolak'], 'layout_public');
            return;
        }

        $laporan = LaporanRepository::findByRegNo($regNo);
        if ($laporan === null) {
            http_response_code(404);
            View::render('errors/404', ['pageTitle' => 'Laporan tidak ditemukan'], 'layout_public');
            return;
        }

        View::render('success', [
            'pageTitle' => 'Laporan berhasil dikirim',
            'laporan'   => $laporan,
        ], 'layout_public');
    }

    private function backToReview(string $message): never
    {
        $_SESSION['_step_result'] = [
            'step'   => 5,
            'errors' => ['_form' => $message],
            'old'    => [],
        ];
        Response::redirect('/langkah/5');
    }

    private function tooMany(int $retry, string $message): never
    {
        http_response_code(429);
        header('Retry-After: ' . max(1, $retry));
        View::render('errors/429', [
            'pageTitle' => 'Batas pengiriman tercapai',
            'retry'     => max(1, $retry),
            'message'   => $message,
            'backUrl'   => '/langkah/5',
        ], 'layout_public');
    }

    private function quotaExceeded(): never
    {
        http_response_code(503);
        View::render('errors/503', ['pageTitle' => 'Kuota harian penuh'], 'layout_public');
    }
}
