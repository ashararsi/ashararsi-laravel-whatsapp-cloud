<?php

namespace Vendor\LaravelWhatsAppCloud\Exceptions;

use Exception;

class WhatsAppException extends Exception
{
    /**
     * @param  array<string, mixed>|null  $response
     */
    public function __construct(
        string $message,
        public readonly ?array $response = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
