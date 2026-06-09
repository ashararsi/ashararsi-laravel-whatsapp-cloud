<?php

namespace Vendor\LaravelWhatsAppCloud\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;

class DoctorCommand extends Command
{
    protected $signature = 'whatsapp:doctor';

    protected $description = 'Run WhatsApp Cloud health checks and print a human-readable report';

    /** @var list<array{level: string, message: string}> */
    protected array $results = [];

    public function handle(): int
    {
        $this->checkDatabase();
        $this->checkQueues();
        $this->checkRoutes();
        $this->checkWebhookConfig();
        $this->checkMetaAccounts();
        $this->checkTwilioAccounts();
        $this->checkStorage();
        $this->checkCache();

        $this->printReport();

        return $this->hasErrors() ? self::FAILURE : self::SUCCESS;
    }

    protected function checkDatabase(): void
    {
        $tables = [
            'whatsapp_accounts',
            'whatsapp_messages',
            'whatsapp_contacts',
            'whatsapp_conversations',
            'whatsapp_conversation_messages',
        ];

        try {
            DB::connection()->getPdo();
            $this->addPass('Database connection is available.');
        } catch (\Throwable $e) {
            $this->addError('Database connection failed: '.$e->getMessage());

            return;
        }

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $this->addPass("Table [{$table}] exists.");
            } else {
                $this->addError("Missing table [{$table}]. Run php artisan migrate.");
            }
        }
    }

    protected function checkQueues(): void
    {
        if (! config('whatsapp.queue_enabled', true)) {
            $this->addWarning('Queue sending is disabled (Admin → Settings → Queue Outgoing Messages).');

            return;
        }

        $connection = config('whatsapp.queue_connection') ?: config('queue.default');

        if ($connection) {
            $this->addPass("Queue connection configured [{$connection}].");
        } else {
            $this->addWarning('No queue connection configured.');
        }
    }

    protected function checkRoutes(): void
    {
        $required = [
            'whatsapp.admin.dashboard',
            'whatsapp.admin.conversations.index',
            'whatsapp.admin.accounts.index',
        ];

        foreach ($required as $name) {
            if (Route::has($name)) {
                $this->addPass("Route [{$name}] is registered.");
            } else {
                $this->addError("Route [{$name}] is missing.");
            }
        }

        $prefix = trim((string) config('whatsapp.webhook.prefix', 'whatsapp'), '/');
        $this->addPass("Meta webhook endpoint: /{$prefix}/webhook");
        $this->addPass("Twilio inbound endpoint: /{$prefix}/twilio/webhook");
        $this->addPass("Twilio status endpoint: /{$prefix}/twilio/status");
    }

    protected function checkWebhookConfig(): void
    {
        if (config('whatsapp.webhook.require_signature', false)) {
            if (config('whatsapp.webhook.app_secret') || WhatsAppAccount::query()->whereNotNull('app_secret')->exists()) {
                $this->addPass('Webhook signature verification enabled with secret configured.');
            } else {
                $this->addError('Webhook signature required but no global or per-account app_secret found.');
            }
        } else {
            $this->addWarning('Webhook signature verification is disabled.');
        }
    }

    protected function checkMetaAccounts(): void
    {
        $accounts = WhatsAppAccount::query()->active()->where('provider', WhatsAppAccount::PROVIDER_META)->get();

        if ($accounts->isEmpty()) {
            $this->addWarning('No active Meta accounts configured.');

            return;
        }

        foreach ($accounts as $account) {
            $missing = array_filter([
                ! $account->phone_number_id ? 'phone_number_id' : null,
                ! $account->access_token ? 'access_token' : null,
            ]);

            if ($missing === []) {
                $this->addPass("Meta account [{$account->name}] credentials look complete.");
            } else {
                $this->addError('Meta account ['.$account->name.'] missing: '.implode(', ', $missing));
            }
        }
    }

    protected function checkTwilioAccounts(): void
    {
        $accounts = WhatsAppAccount::query()->active()->where('provider', WhatsAppAccount::PROVIDER_TWILIO)->get();

        if ($accounts->isEmpty()) {
            $this->addWarning('No active Twilio accounts configured.');

            return;
        }

        foreach ($accounts as $account) {
            $missing = array_filter([
                ! $account->twilio_sid ? 'twilio_sid' : null,
                ! $account->twilio_token ? 'twilio_token' : null,
                ! $account->twilio_whatsapp_number ? 'twilio_whatsapp_number' : null,
            ]);

            if ($missing === []) {
                $this->addPass("Twilio account [{$account->name}] credentials look complete.");
            } else {
                $this->addError('Twilio account ['.$account->name.'] missing: '.implode(', ', $missing));
            }
        }
    }

    protected function checkStorage(): void
    {
        $disk = config('whatsapp.media.disk', 'local');

        try {
            $path = storage_path('app');
            if (is_writable($path)) {
                $this->addPass("Storage path is writable [{$path}].");
            } else {
                $this->addWarning("Storage path may not be writable [{$path}].");
            }

            $this->addPass("Media disk configured [{$disk}].");
        } catch (\Throwable $e) {
            $this->addWarning('Storage check skipped: '.$e->getMessage());
        }
    }

    protected function checkCache(): void
    {
        if (! config('whatsapp.cache.enabled', true)) {
            $this->addWarning('Account cache is disabled.');

            return;
        }

        try {
            Cache::put('whatsapp_doctor_probe', 'ok', 10);
            $value = Cache::get('whatsapp_doctor_probe');
            Cache::forget('whatsapp_doctor_probe');

            if ($value === 'ok') {
                $this->addPass('Cache store is operational.');
            } else {
                $this->addWarning('Cache store did not return expected probe value.');
            }
        } catch (\Throwable $e) {
            $this->addWarning('Cache check failed: '.$e->getMessage());
        }
    }

    protected function addPass(string $message): void
    {
        $this->results[] = ['level' => 'PASS', 'message' => $message];
    }

    protected function addWarning(string $message): void
    {
        $this->results[] = ['level' => 'WARNING', 'message' => $message];
    }

    protected function addError(string $message): void
    {
        $this->results[] = ['level' => 'ERROR', 'message' => $message];
    }

    protected function printReport(): void
    {
        $this->newLine();
        $this->line('<fg=cyan>WhatsApp Cloud Doctor Report</>');
        $this->line(str_repeat('-', 60));

        foreach ($this->results as $result) {
            $color = match ($result['level']) {
                'PASS' => 'green',
                'WARNING' => 'yellow',
                default => 'red',
            };

            $this->line("<fg={$color}>[{$result['level']}]</> {$result['message']}");
        }

        $this->line(str_repeat('-', 60));
        $this->line(sprintf(
            'Summary: %d pass, %d warning, %d error',
            collect($this->results)->where('level', 'PASS')->count(),
            collect($this->results)->where('level', 'WARNING')->count(),
            collect($this->results)->where('level', 'ERROR')->count(),
        ));
    }

    protected function hasErrors(): bool
    {
        return collect($this->results)->contains(fn ($r) => $r['level'] === 'ERROR');
    }
}
