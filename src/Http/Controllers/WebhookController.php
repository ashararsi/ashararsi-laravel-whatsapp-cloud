<?php

namespace Vendor\LaravelWhatsAppCloud\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Vendor\LaravelWhatsAppCloud\Services\WebhookHandler;

class WebhookController extends Controller
{
    public function __construct(
        protected WebhookHandler $handler,
    ) {}

    public function verify(Request $request): Response|string
    {
        return $this->handler->verify($request);
    }

    public function handle(Request $request): Response
    {
        return $this->handler->handle($request);
    }
}
