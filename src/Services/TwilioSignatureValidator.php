<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Illuminate\Http\Request;

class TwilioSignatureValidator
{
    public function isValid(Request $request, string $authToken): bool
    {
        if (! config('whatsapp.twilio.require_signature', true)) {
            return true;
        }

        $signature = $request->header('X-Twilio-Signature');

        if (! is_string($signature) || $signature === '') {
            return false;
        }

        $expected = $this->computeSignature($request->fullUrl(), $request->post(), $authToken);

        return hash_equals($expected, $signature);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function computeSignature(string $url, array $params, string $authToken): string
    {
        ksort($params);

        $data = $url;

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            $data .= $key.(string) $value;
        }

        return base64_encode(hash_hmac('sha1', $data, $authToken, true));
    }
}
