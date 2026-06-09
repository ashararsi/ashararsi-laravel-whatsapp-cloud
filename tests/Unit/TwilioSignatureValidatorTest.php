<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Services\TwilioSignatureValidator;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class TwilioSignatureValidatorTest extends TestCase
{
    #[Test]
    public function it_validates_twilio_request_signature(): void
    {
        config(['whatsapp.twilio.require_signature' => true]);

        $url = 'https://example.com/whatsapp/twilio/webhook';
        $params = [
            'AccountSid' => 'ACtest',
            'Body' => 'Hello',
        ];

        $validator = new TwilioSignatureValidator;
        $signature = $validator->computeSignature($url, $params, 'auth-token');

        $request = Request::create($url, 'POST', $params);
        $request->headers->set('X-Twilio-Signature', $signature);

        $this->assertTrue($validator->isValid($request, 'auth-token'));
    }
}
