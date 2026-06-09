<?php

namespace Vendor\LaravelWhatsAppCloud\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Vendor\LaravelWhatsAppCloud\Services\DashboardService;

class DashboardController extends Controller
{
    public function __invoke(DashboardService $dashboard): View
    {
        return view('whatsapp::admin.dashboard', [
            'stats' => $dashboard->stats(),
        ]);
    }
}
