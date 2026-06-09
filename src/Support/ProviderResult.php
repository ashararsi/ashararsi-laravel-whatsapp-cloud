<?php

namespace Vendor\LaravelWhatsAppCloud\Support;

class ProviderResult
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $response
     */
    public function __construct(
        public readonly array $payload,
        public readonly array $response,
    ) {}
}
