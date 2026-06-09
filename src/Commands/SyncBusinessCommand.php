<?php

namespace Vendor\LaravelWhatsAppCloud\Commands;

use Illuminate\Console\Command;
use Vendor\LaravelWhatsAppCloud\Services\BusinessProfileSyncService;

class SyncBusinessCommand extends Command
{
    protected $signature = 'whatsapp:sync-business
                            {--account= : Sync a specific account by ID or name}';

    protected $description = 'Sync WhatsApp business profile from Meta Graph API';

    public function handle(BusinessProfileSyncService $service): int
    {
        $accounts = $service->resolveAccountsForSync($this->option('account'));

        if ($accounts->isEmpty()) {
            $this->warn('No active Meta accounts found to sync.');

            return self::FAILURE;
        }

        foreach ($accounts as $account) {
            try {
                $profile = $service->syncAccount($account);
                $this->info("Synced business profile for [{$account->name}]: {$profile->business_name}");
            } catch (\Throwable $e) {
                $this->error("Failed for [{$account->name}]: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
