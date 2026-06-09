<?php

namespace Vendor\LaravelWhatsAppCloud\Commands;

use Illuminate\Console\Command;
use Vendor\LaravelWhatsAppCloud\Services\PhoneNumberSyncService;

class SyncNumbersCommand extends Command
{
    protected $signature = 'whatsapp:sync-numbers
                            {--account= : Sync a specific account by ID or name}';

    protected $description = 'Sync WhatsApp phone numbers from Meta Graph API';

    public function handle(PhoneNumberSyncService $service): int
    {
        $accounts = $service->resolveAccountsForSync($this->option('account'));

        if ($accounts->isEmpty()) {
            $this->warn('No active Meta accounts found to sync.');

            return self::FAILURE;
        }

        foreach ($accounts as $account) {
            try {
                $count = $service->syncAccount($account);
                $this->info("Synced {$count} phone number(s) for [{$account->name}].");
            } catch (\Throwable $e) {
                $this->error("Failed for [{$account->name}]: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
