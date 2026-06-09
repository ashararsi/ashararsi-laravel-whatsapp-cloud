<?php

namespace Vendor\LaravelWhatsAppCloud\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Vendor\LaravelWhatsAppCloud\Services\TwilioWebhookHandler;

class TwilioWebhookController extends Controller
{
    public function __construct(
        protected TwilioWebhookHandler $handler,
    ) {}

    public function inbound(Request $request): Response
    {
        return $this->handler->handleInbound($request);
    }

    public function status(Request $request): Response
    {
        return $this->handler->handleStatus($request);
    }
}
