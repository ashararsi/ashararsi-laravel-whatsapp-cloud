<?php

namespace Vendor\LaravelWhatsAppCloud\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'whatsapp:install';

    protected $description = 'Publish WhatsApp Cloud package config, migrations, and views';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--tag' => 'whatsapp-config',
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'whatsapp-migrations',
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'whatsapp-views',
        ]);

        $this->components->info('WhatsApp Cloud package installed successfully.');
        $this->components->warn('Run `php artisan migrate` to create database tables.');

        return self::SUCCESS;
    }
}
