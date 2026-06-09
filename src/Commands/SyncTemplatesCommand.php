<?php

namespace Vendor\LaravelWhatsAppCloud\Commands;

use Illuminate\Console\Command;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Services\TemplateSyncService;

class SyncTemplatesCommand extends Command
{
    protected $signature = 'whatsapp:templates:sync {--account= : Account ID or name}';

    protected $description = 'Sync WhatsApp message templates from Meta Cloud API';

    public function handle(TemplateSyncService $sync): int
    {
        $accounts = WhatsAppAccount::query()->active()->when(
            $this->option('account'),
            fn ($q, $value) => $q->where('id', $value)->orWhere('name', $value),
        )->get();

        if ($accounts->isEmpty()) {
            $this->error('No matching active accounts found.');

            return self::FAILURE;
        }

        foreach ($accounts as $account) {
            $count = $sync->syncAccount($account);
            $this->info("Synced {$count} templates for [{$account->name}].");
        }

        return self::SUCCESS;
    }
}
