<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Services\FlowService;

final class HomeController
{
    public function index(): void
    {
        $wizard = FlowService::get();
        if ($wizard !== null) {
            $step = FlowService::currentStep($wizard);
            Response::redirect('/langkah/' . $step);
        }
        Response::redirect('/langkah/1');
    }
}
