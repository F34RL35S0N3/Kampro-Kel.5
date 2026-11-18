<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Response;
use App\Core\View;
use App\Services\FlowService;
use App\Services\LimitService;
use App\Support\Logger;
use App\Validation\AnggotaValidator;
use App\Validation\KetuaValidator;
use App\Validation\LuaranValidator;
use App\Validation\RincianValidator;

final class WizardController
{
    private const SECURITY_KEYS = ['_csrf', '_flow', '_hp_website'];

    public function show(array $params): void
    {
        $step = (int) ($params['step'] ?? 0);
        if ($step < 1 || $step > 5) {
            Response::redirect('/langkah/1');
        }

        $wizard = FlowService::touch();
        if ($wizard === null) {
            $wizard = FlowService::start();
            Logger::event('WIZARD_STARTED', ['step' => $step]);
        }

        if (!FlowService::canAccess($wizard, $step)) {
            Logger::warning('FLOW_VIOLATION', ['requested' => $step, 'allowed' => FlowService::currentStep($wizard)]);
            Response::redirect('/langkah/' . FlowService::currentStep($wizard));
        }

        // Ambil hasil validasi gagal (PRG) bila ada.
        $pending = $_SESSION['_step_result'] ?? null;
        $errors = [];
        $old = [];
        if (is_array($pending) && (int) ($pending['step'] ?? 0) === $step) {
            $errors = (array) ($pending['errors'] ?? []);
            $old = (array) ($pending['old'] ?? []);
            unset($_SESSION['_step_result']);
        }

        $flowToken = FlowService::issueFlow($wizard, $step);

        $data = [
            'pageTitle'  => 'Langkah ' . $step . ' — ' . step_label($step),
            'step'       => $step,
            'wizard'     => $wizard,
            'errors'     => $errors,
            'flowToken'  => $flowToken,
            'completed'  => (int) $wizard['flow_done'],
            'progress'   => (int) round(((int) $wizard['flow_done'] / 5) * 100),
        ];

        if ($step === 5) {
            $idemKey = FlowService::ensureIdemKey($wizard);
            FlowService::save($wizard);
            $data['idemKey'] = $idemKey;
            $data['review'] = FlowService::allData($wizard);
            $data['submittedNotice'] = !empty($_SESSION['_submitted_notice']);
            unset($_SESSION['_submitted_notice']);
            $data['errors'] = $errors;
            View::render('wizard/step5', $data, 'layout');
            return;
        }

        $padRows = isset($_GET['rows']) && is_numeric($_GET['rows']) ? (int) $_GET['rows'] : 0;
        $form = $this->formData($wizard, $step, $padRows);
        if ($old !== []) {
            $form = $this->mergeOld($form, $old, $step);
        }
        $data['form'] = $form;

        FlowService::save($wizard);
        View::render('wizard/step' . $step, $data, 'layout');
    }

    public function post(array $params): void
    {
        $step = (int) ($params['step'] ?? 0);
        if ($step < 1 || $step > 4) {
            Response::redirect('/langkah/5');
        }

        $wizard = FlowService::touch();
        if ($wizard === null) {
            Response::redirect('/langkah/1');
        }

        if (!Csrf::verify(is_string($_POST['_csrf'] ?? null) ? $_POST['_csrf'] : null)) {
            Logger::warning('CSRF_FAIL', ['step' => $step]);
            http_response_code(419);
            View::render('errors/419', ['pageTitle' => 'Sesi kedaluwarsa'], 'layout_public');
            return;
        }

        // AA-02 — honeypot: tolak diam-diam, log, tanpa memberi petunjuk.
        if (trim((string) ($_POST['_hp_website'] ?? '')) !== '') {
            Logger::warning('HONEYPOT_HIT', ['step' => $step]);
            Response::redirect('/langkah/' . FlowService::currentStep($wizard));
        }

        if (!FlowService::canAccess($wizard, $step)) {
            Logger::warning('FLOW_VIOLATION', ['requested' => $step, 'allowed' => FlowService::currentStep($wizard)]);
            Response::redirect('/langkah/' . FlowService::currentStep($wizard));
        }

        // AA-07 / LIM-04 — penundaan progresif & penguncian sesi.
        $block = LimitService::validationBlockSeconds();
        if ($block > 0) {
            http_response_code(429);
            header('Retry-After: ' . $block);
            View::render('errors/429', [
                'pageTitle' => 'Terlalu banyak percobaan',
                'retry'     => $block,
            ], 'layout_public');
            return;
        }

        // AA-05 / AA-06 — token alur bertanda tangan.
        $flowCheck = FlowService::verifyFlow($wizard, $step, is_string($_POST['_flow'] ?? null) ? $_POST['_flow'] : null);
        if (!$flowCheck['ok']) {
            Logger::warning('FLOW_TOKEN_INVALID', ['step' => $step, 'reason' => $flowCheck['reason']]);
            $this->redirectWithError($step, ['_form' => 'Formulir kedaluwarsa atau dimuat ulang. Silakan isi kembali langkah ini.'], $_POST);
            return;
        }

        // V2.2.1 — kunci parameter tak dikenal ditolak.
        $allowed = $this->allowedKeys($step);
        $unknown = array_diff(array_keys($_POST), $allowed);
        if ($unknown !== []) {
            Logger::warning('UNKNOWN_PARAMETERS', ['step' => $step, 'keys' => array_values($unknown)]);
            $this->redirectWithError($step, ['_form' => 'Permintaan tidak valid.'], $_POST);
            return;
        }

        [$data, $errors] = $this->validateStep($wizard, $step, $_POST);

        if ($errors !== []) {
            LimitService::registerValidationFailure(\App\Core\Session::id());
            Logger::event('VALIDATION_FAIL', ['step' => $step, 'fields' => array_keys($errors)]);
            $this->redirectWithError($step, $errors, $_POST);
            return;
        }

        LimitService::clearValidationDelay();
        FlowService::setStepData($wizard, $step, $data);
        FlowService::save($wizard);

        Logger::event('STEP_SAVED', ['step' => $step]);
        Response::redirect($step < 4 ? '/langkah/' . ($step + 1) : '/langkah/5');
    }

    public function reset(): void
    {
        if (!Csrf::verify(is_string($_POST['_csrf'] ?? null) ? $_POST['_csrf'] : null)) {
            Response::redirect('/');
        }
        FlowService::reset();
        Logger::event('WIZARD_RESET');
        Response::redirect('/langkah/1');
    }

    /** @return array{0:array,1:array} */
    private function validateStep(array $wizard, int $step, array $input): array
    {
        if ($step === 1) {
            $result = KetuaValidator::validate($input);
            return [$result['data'], $result['errors']];
        }

        if ($step === 2) {
            $ketua = FlowService::stepData($wizard, 1);
            if (($ketua['ketua_nip'] ?? '') === '') {
                Response::redirect('/langkah/1');
            }
            $result = AnggotaValidator::validate($input, (string) $ketua['ketua_nip']);
            return [$result['data'], $result['errors']];
        }

        if ($step === 3) {
            $anggota = FlowService::stepData($wizard, 2);
            $result = RincianValidator::validate($input, count($anggota));
            return [$result['data'], $result['errors']];
        }

        $rincian = FlowService::stepData($wizard, 3);
        $result = LuaranValidator::validate(
            $input,
            (string) ($rincian['jenis_penelitian'] ?? ''),
            (int) ($rincian['lama_minggu'] ?? 0)
        );
        return [$result['data'], $result['errors']];
    }

    private function redirectWithError(int $step, array $errors, array $old): void
    {
        $_SESSION['_step_result'] = [
            'step'   => $step,
            'errors' => $errors,
            'old'    => $this->sanitizeOld($old),
        ];
        Response::redirect('/langkah/' . $step);
    }

    private function sanitizeOld(array $input): array
    {
        $clean = [];
        foreach ($input as $key => $value) {
            if (in_array($key, self::SECURITY_KEYS, true)) {
                continue;
            }
            if (is_array($value)) {
                $clean[$key] = $this->sanitizeOld($value);
            } elseif (is_scalar($value)) {
                $clean[$key] = (string) $value;
            }
        }
        return $clean;
    }

    private function allowedKeys(int $step): array
    {
        $base = self::SECURITY_KEYS;
        return match ($step) {
            1 => array_merge($base, KetuaValidator::FIELDS),
            2 => array_merge($base, ['anggota']),
            3 => array_merge($base, [
                'lama_minggu', 'biaya_rupiah', 'jenis_penelitian', 'cpl_prodi',
                'tema_rip', 'mitra', 'potensi_adopsi',
            ]),
            4 => array_merge($base, ['luaran']),
            default => $base,
        };
    }

    private function formData(array $wizard, int $step, int $padRows = 0): array
    {
        if ($step === 1) {
            $ketua = FlowService::stepData($wizard, 1);
            $defaults = array_fill_keys(KetuaValidator::FIELDS, '');
            return array_merge($defaults, $ketua);
        }

        if ($step === 2) {
            $anggota = FlowService::stepData($wizard, 2);
            if ($anggota === []) {
                $anggota = [['nama' => '', 'nip_npm' => '', 'bidang_kelas' => '']];
            }
            $max = (int) config('validation.rows.anggota_max', 10);
            $target = min($max, max(count($anggota), $padRows));
            while (count($anggota) < $target) {
                $anggota[] = ['nama' => '', 'nip_npm' => '', 'bidang_kelas' => ''];
            }
            return ['anggota' => $anggota];
        }

        if ($step === 3) {
            $rincian = FlowService::stepData($wizard, 3);
            return array_merge([
                'lama_minggu'      => '',
                'biaya_rupiah'     => '',
                'jenis_penelitian' => '',
                'cpl_prodi'        => '',
                'tema_rip'         => '',
                'mitra'            => '',
                'potensi_adopsi'   => '',
            ], $rincian);
        }

        $luaran = FlowService::stepData($wizard, 4);
        if ($luaran === []) {
            $luaran = [['jenis' => '', 'nama' => '', 'jumlah' => '']];
        }
        $max = (int) config('validation.rows.luaran_max', 10);
        $target = min($max, max(count($luaran), $padRows));
        while (count($luaran) < $target) {
            $luaran[] = ['jenis' => '', 'nama' => '', 'jumlah' => ''];
        }
        return ['luaran' => $luaran];
    }

    private function mergeOld(array $form, array $old, int $step): array
    {
        if ($step === 2 || $step === 4) {
            $key = $step === 2 ? 'anggota' : 'luaran';
            if (isset($old[$key]) && is_array($old[$key]) && $old[$key] !== []) {
                $form[$key] = $old[$key];
            }
            return $form;
        }

        foreach ($old as $key => $value) {
            if (is_string($value)) {
                $form[$key] = $value;
            }
        }
        return $form;
    }
}
