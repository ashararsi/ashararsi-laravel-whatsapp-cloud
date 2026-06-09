<?php

namespace Vendor\LaravelWhatsAppCloud\Commands;

use Illuminate\Console\Command;
use Vendor\LaravelWhatsAppCloud\Services\TemplateSyncService;

class SyncTemplatesCommand extends Command
{
    protected $signature = 'whatsapp:templates:sync
                            {--account= : Account ID or name}
                            {--provider= : Provider filter (meta, twilio)}';

    protected $description = 'Sync WhatsApp message templates from Meta Cloud API';

    public function handle(TemplateSyncService $sync): int
    {
        $accounts = $sync->resolveAccountsForSync(
            $this->option('account'),
            $this->option('provider'),
        );

        if ($accounts->isEmpty()) {
            $this->error('No matching active accounts found.');

            return self::FAILURE;
        }

        $total = 0;

        foreach ($accounts as $account) {
            if (! $account->isMeta()) {
                $this->warn("Skipping [{$account->name}] — template sync supports Meta accounts only.");

                continue;
            }

            try {
                $count = $sync->syncAccount($account);
                $total += $count;
                $this->info("Synced {$count} templates for [{$account->name}].");
            } catch (\Throwable $e) {
                $this->error("Failed for [{$account->name}]: {$e->getMessage()}");
            }
        }

        $this->info("Processed {$total} template(s) across {$accounts->count()} account(s).");

        return self::SUCCESS;
    }
}
