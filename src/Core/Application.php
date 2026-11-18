<?php
declare(strict_types=1);

namespace App\Core;

use App\Controllers\AdminController;
use App\Controllers\HomeController;
use App\Controllers\SubmitController;
use App\Controllers\WizardController;
use App\Support\Logger;
use Throwable;

final class Application
{
    public function run(): void
    {
        $request = new Request();

        try {
            $maxBody = (int) config('limits.max_body_bytes', 65536);
            if ($request->isPost() && $request->exceedsBodyLimit($maxBody)) {
                Logger::warning('REQUEST_TOO_LARGE', ['bytes' => $request->contentLength()]);
                http_response_code(413);
                View::render('errors/413', ['pageTitle' => 'Permintaan terlalu besar'], 'layout_public');
                return;
            }

            $router = new Router();
            $this->registerRoutes($router);
            $router->dispatch($request);
        } catch (Throwable $e) {
            Logger::error('UNHANDLED_EXCEPTION', [
                'type'    => $e::class,
                'message' => $e->getMessage(),
                'file'    => basename($e->getFile()),
                'line'    => $e->getLine(),
            ]);

            http_response_code(500);
            $debug = (bool) config('app.debug', false);
            View::render('errors/500', [
                'pageTitle' => 'Terjadi kesalahan',
                'detail'    => $debug ? $e->getMessage() : null,
            ], 'layout_public');
        }
    }

    private function registerRoutes(Router $router): void
    {
        $router->get('/', [HomeController::class, 'index']);

        $router->get('/langkah/{step}', [WizardController::class, 'show']);
        $router->post('/langkah/{step}', [WizardController::class, 'post']);
        $router->post('/reset', [WizardController::class, 'reset']);

        $router->post('/kirim', [SubmitController::class, 'submit']);
        $router->get('/sukses/{reg}', [SubmitController::class, 'success']);

        $router->get('/admin', [AdminController::class, 'loginForm']);
        $router->get('/admin/login', [AdminController::class, 'loginForm']);
        $router->post('/admin/login', [AdminController::class, 'login']);
        $router->post('/admin/logout', [AdminController::class, 'logout']);
        $router->get('/admin/laporan', [AdminController::class, 'index']);
        $router->get('/admin/laporan/{id}', [AdminController::class, 'show']);
        $router->post('/admin/laporan/{id}/setujui', [AdminController::class, 'approve']);
        $router->post('/admin/laporan/{id}/tolak', [AdminController::class, 'reject']);
    }
}
