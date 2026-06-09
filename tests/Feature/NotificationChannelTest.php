<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use Illuminate\Notifications\Notification;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Contracts\WhatsAppClientInterface;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Notifications\WhatsAppChannel;
use Vendor\LaravelWhatsAppCloud\Tests\Mocks\MockWhatsAppClient;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class NotificationChannelTest extends TestCase
{
    #[Test]
    public function it_sends_notification_via_whatsapp_channel(): void
    {
        $mock = new MockWhatsAppClient;
        $this->app->instance(WhatsAppClientInterface::class, $mock);

        WhatsAppAccount::query()->create([
            'name' => 'default',
            'phone_number' => '923001234567',
            'phone_number_id' => '123',
            'provider' => 'meta',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        $notifiable = new class
        {
            public string $phone = '923009999999';
        };

        $notification = new class extends Notification
        {
            public function toWhatsApp($notifiable): array
            {
                return [
                    'text' => 'Order shipped!',
                ];
            }
        };

        $channel = $this->app->make(WhatsAppChannel::class);
        $channel->send($notifiable, $notification);

        $this->assertCount(1, $mock->sent);
        $this->assertSame('923009999999', $mock->sent[0]['payload']['to']);
    }
}
