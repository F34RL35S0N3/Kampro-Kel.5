<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Core\Response;
use App\Core\View;
use App\Repositories\AdminRepository;
use App\Repositories\LaporanRepository;
use App\Services\ApprovalService;
use App\Services\RateLimiter;
use App\Support\Logger;

final class AdminController
{
    public function loginForm(): void
    {
        if ($this->admin() !== null) {
            Response::redirect('/admin/laporan');
        }
        View::render('admin/login', [
            'pageTitle' => 'Masuk Admin',
            'flash'     => flash_get('admin_login_error'),
            'username'  => '',
        ], 'layout_public');
    }

    public function login(): void
    {
        if ($this->admin() !== null) {
            Response::redirect('/admin/laporan');
        }

        if (!Csrf::verify(is_string($_POST['_csrf'] ?? null) ? $_POST['_csrf'] : null)) {
            Response::redirect('/admin/login');
        }

        $limiter = new RateLimiter();
        $bucket = 'login:' . ip_hash(request_ip(), (string) config('app.hmac_key'));
        if ($limiter->count($bucket, 900) >= 10) {
            Logger::warning('ADMIN_LOGIN_RATE_LIMITED');
            http_response_code(429);
            header('Retry-After: ' . $limiter->retryAfter($bucket, 900));
            View::render('errors/429', [
                'pageTitle' => 'Terlalu banyak percobaan',
                'retry'     => $limiter->retryAfter($bucket, 900),
            ], 'layout_public');
            return;
        }

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || strlen($username) > 50 || $password === '' || strlen($password) > 200) {
            $limiter->hit($bucket);
            flash_set('admin_login_error', 'Username atau password salah.');
            Response::redirect('/admin/login');
        }

        $admin = AdminRepository::authenticate($username, $password);
        if ($admin === null) {
            $limiter->hit($bucket);
            Logger::warning('ADMIN_LOGIN_FAIL', ['username_length' => strlen($username)]);
            flash_set('admin_login_error', 'Username atau password salah.');
            Response::redirect('/admin/login');
        }

        Session::regenerate();
        $_SESSION['admin'] = [
            'id'       => (int) $admin['id'],
            'username' => (string) $admin['username'],
            'role'     => (string) $admin['role'],
        ];

        Logger::event('ADMIN_LOGIN', ['admin_id' => (int) $admin['id'], 'role' => $admin['role']]);
        Response::redirect('/admin/laporan');
    }

    public function logout(): void
    {
        if (!Csrf::verify(is_string($_POST['_csrf'] ?? null) ? $_POST['_csrf'] : null)) {
            Response::redirect('/admin/laporan');
        }
        $admin = $this->admin();
        if ($admin !== null) {
            Logger::event('ADMIN_LOGOUT', ['admin_id' => $admin['id']]);
        }
        unset($_SESSION['admin']);
        Session::regenerate();
        Response::redirect('/admin/login');
    }

    public function index(): void
    {
        $admin = $this->requireAdmin();

        $search = trim((string) ($_GET['q'] ?? ''));
        $search = mb_substr($search, 0, 100);
        $status = trim((string) ($_GET['status'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $result = LaporanRepository::paginate($search, $status, $page, 15);
        $totalPages = max(1, (int) ceil($result['total'] / 15));

        View::render('admin/list', [
            'pageTitle'   => 'Daftar Laporan',
            'admin'       => $admin,
            'rows'        => $result['rows'],
            'total'       => $result['total'],
            'page'        => $page,
            'totalPages'  => $totalPages,
            'search'      => $search,
            'status'      => $status,
            'counts'      => LaporanRepository::statusCounts(),
            'flash'       => flash_get('admin_flash'),
        ], 'layout_admin');
    }

    public function show(array $params): void
    {
        $admin = $this->requireAdmin();
        $id = (int) ($params['id'] ?? 0);
        $laporan = $id > 0 ? LaporanRepository::findById($id) : null;

        if ($laporan === null) {
            http_response_code(404);
            View::render('admin/notfound', ['pageTitle' => 'Laporan tidak ditemukan', 'admin' => $admin], 'layout_admin');
            return;
        }

        View::render('admin/detail', [
            'pageTitle' => 'Detail ' . $laporan['reg_no'],
            'admin'     => $admin,
            'laporan'   => $laporan,
            'flash'     => flash_get('admin_flash'),
        ], 'layout_admin');
    }

    public function approve(array $params): void
    {
        $this->decide($params, 'SETUJU');
    }

    public function reject(array $params): void
    {
        $this->decide($params, 'TOLAK');
    }

    private function decide(array $params, string $decision): void
    {
        $admin = $this->requireAdmin();

        if (!Csrf::verify(is_string($_POST['_csrf'] ?? null) ? $_POST['_csrf'] : null)) {
            Response::redirect('/admin/laporan');
        }

        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            Response::redirect('/admin/laporan');
        }

        $result = ApprovalService::decide(
            $id,
            (int) $admin['id'],
            $decision,
            (string) $admin['username'],
            ip_hash(request_ip(), (string) config('app.hmac_key'))
        );

        $messages = [
            'updated'         => $decision === 'SETUJU'
                ? 'Persetujuan tercatat. Status laporan: ' . ($result['status'] ?? '-') . '.'
                : 'Laporan ditolak.',
            'already_voted'   => 'Anda sudah memberikan keputusan untuk laporan ini.',
            'already_approved'=> 'Laporan ini sudah disetujui.',
            'already_rejected'=> 'Laporan ini sudah ditolak.',
            'not_found'       => 'Laporan tidak ditemukan.',
        ];

        flash_set('admin_flash', $messages[$result['code']] ?? 'Keputusan tidak dapat diproses.');

        Logger::event('ADMIN_DECISION', [
            'admin_id' => (int) $admin['id'],
            'laporan_id' => $id,
            'decision' => $decision,
            'code' => $result['code'],
        ]);

        Response::redirect('/admin/laporan/' . $id);
    }

    private function admin(): ?array
    {
        $admin = $_SESSION['admin'] ?? null;
        return is_array($admin) && isset($admin['id']) ? $admin : null;
    }

    private function requireAdmin(): array
    {
        $admin = $this->admin();
        if ($admin === null) {
            Response::redirect('/admin/login');
        }
        return $admin;
    }
}
