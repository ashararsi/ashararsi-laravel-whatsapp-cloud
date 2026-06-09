<?php

namespace Vendor\LaravelWhatsAppCloud\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Vendor\LaravelWhatsAppCloud\Services\AnalyticsService;

class DashboardController extends Controller
{
    public function __invoke(AnalyticsService $analytics): View
    {
        return view('whatsapp::admin.dashboard', [
            'stats' => $analytics->overview(),
        ]);
    }
}
