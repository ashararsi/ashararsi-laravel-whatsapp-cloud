<?php

namespace Vendor\LaravelWhatsAppCloud\Contracts;

use Vendor\LaravelWhatsAppCloud\Support\ProviderResult;

interface SupportsInteractiveMessages
{
    /**
     * @param  array<int, array<string, string>>  $buttons  [['id' => '1', 'title' => 'Yes'], ...]
     */
    public function sendButtons(
        string $to,
        string $body,
        array $buttons,
        ?string $header = null,
        ?string $footer = null,
    ): ProviderResult;

    /**
     * @param  array<int, array<string, mixed>>  $sections
     */
    public function sendList(
        string $to,
        string $body,
        string $buttonText,
        array $sections,
        ?string $header = null,
        ?string $footer = null,
    ): ProviderResult;
}
