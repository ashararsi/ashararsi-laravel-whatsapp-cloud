<?php

namespace Vendor\LaravelWhatsAppCloud\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Vendor\LaravelWhatsAppCloud\Services\SystemHealthService;

class SystemController extends Controller
{
    public function __invoke(SystemHealthService $health): View
    {
        return view('whatsapp::admin.system', [
            'health' => $health->overview(),
        ]);
    }
}
