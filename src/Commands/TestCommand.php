<?php

namespace Vendor\LaravelWhatsAppCloud\Commands;

use Illuminate\Console\Command;
use Vendor\LaravelWhatsAppCloud\Facades\WhatsApp;

class TestCommand extends Command
{
    protected $signature = 'whatsapp:test
                            {to : Recipient phone number}
                            {message? : Message text}
                            {--account= : Account ID or name}';

    protected $description = 'Send a test WhatsApp message';

    public function handle(): int
    {
        $to = $this->argument('to');
        $message = $this->argument('message') ?? 'Hello from Laravel WhatsApp Cloud!';
        $account = $this->option('account');

        try {
            $manager = $account
                ? WhatsApp::account($account)
                : WhatsApp::getFacadeRoot();

            $result = $manager->sendText($to, $message);

            $this->components->info('Test message sent successfully.');
            $this->line('Status: '.$result->status);

            if ($result->response_json) {
                $this->line('Response: '.json_encode($result->response_json, JSON_PRETTY_PRINT));
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->components->error('Failed to send test message: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
