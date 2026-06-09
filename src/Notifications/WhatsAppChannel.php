<?php

namespace Vendor\LaravelWhatsAppCloud\Notifications;

use Illuminate\Notifications\Notification;
use Vendor\LaravelWhatsAppCloud\Services\WhatsAppManager;

class WhatsAppChannel
{
    public function __construct(
        protected WhatsAppManager $whatsapp,
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $message = $notification->toWhatsApp($notifiable);

        if (! is_array($message)) {
            return;
        }

        $to = $message['to'] ?? $this->resolvePhone($notifiable);

        if (! $to) {
            return;
        }

        $manager = $this->whatsapp;

        if (isset($message['account'])) {
            $manager = $manager->account($message['account']);
        } elseif (isset($message['using'])) {
            $manager = $manager->using($message['using']);
        }

        if ($message['queue'] ?? false) {
            $manager = $manager->queue();
        }

        $type = $message['type'] ?? 'text';

        match ($type) {
            'template' => $manager->sendTemplate(
                $to,
                $message['template'] ?? $message['name'],
                $message['language'] ?? 'en_US',
                $message['components'] ?? [],
            ),
            'image' => $manager->sendImage(
                $to,
                $message['link'],
                $message['caption'] ?? null,
            ),
            'document' => $manager->sendDocument(
                $to,
                $message['link'],
                $message['filename'] ?? null,
                $message['caption'] ?? null,
            ),
            'audio' => $manager->sendAudio($to, $message['link']),
            'video' => $manager->sendVideo(
                $to,
                $message['link'],
                $message['caption'] ?? null,
            ),
            'location' => $manager->sendLocation(
                $to,
                (float) $message['latitude'],
                (float) $message['longitude'],
                $message['name'] ?? null,
                $message['address'] ?? null,
            ),
            default => $manager->sendText(
                $to,
                $message['text'] ?? $message['message'] ?? '',
                $message['preview_url'] ?? false,
            ),
        };
    }

    protected function resolvePhone(object $notifiable): ?string
    {
        if (method_exists($notifiable, 'routeNotificationForWhatsApp')) {
            return $notifiable->routeNotificationForWhatsApp();
        }

        if (isset($notifiable->phone)) {
            return (string) $notifiable->phone;
        }

        if (isset($notifiable->phone_number)) {
            return (string) $notifiable->phone_number;
        }

        return null;
    }
}
