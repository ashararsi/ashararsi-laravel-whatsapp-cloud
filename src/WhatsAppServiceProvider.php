<?php

namespace Vendor\LaravelWhatsAppCloud;

use Illuminate\Support\ServiceProvider;
use Vendor\LaravelWhatsAppCloud\Commands\InstallCommand;
use Vendor\LaravelWhatsAppCloud\Commands\TestCommand;
use Vendor\LaravelWhatsAppCloud\Contracts\AccountResolverInterface;
use Vendor\LaravelWhatsAppCloud\Contracts\ConversationRecorderInterface;
use Vendor\LaravelWhatsAppCloud\Contracts\MessageLoggerInterface;
use Vendor\LaravelWhatsAppCloud\Contracts\WhatsAppClientInterface;
use Vendor\LaravelWhatsAppCloud\Notifications\WhatsAppChannel;
use Vendor\LaravelWhatsAppCloud\Services\AccountResolver;
use Vendor\LaravelWhatsAppCloud\Services\ConversationService;
use Vendor\LaravelWhatsAppCloud\Services\DashboardService;
use Vendor\LaravelWhatsAppCloud\Services\MessageLogger;
use Vendor\LaravelWhatsAppCloud\Services\ProviderFactory;
use Vendor\LaravelWhatsAppCloud\Services\WebhookHandler;
use Vendor\LaravelWhatsAppCloud\Services\WebhookSignatureValidator;
use Vendor\LaravelWhatsAppCloud\Services\WhatsAppClient;
use Vendor\LaravelWhatsAppCloud\Services\WhatsAppManager;

class WhatsAppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/whatsapp.php', 'whatsapp');

        $this->app->singleton(WhatsAppClientInterface::class, WhatsAppClient::class);
        $this->app->singleton(ProviderFactory::class);
        $this->app->singleton(AccountResolverInterface::class, AccountResolver::class);
        $this->app->singleton(MessageLoggerInterface::class, MessageLogger::class);
        $this->app->singleton(ConversationRecorderInterface::class, ConversationService::class);
        $this->app->singleton(ConversationService::class);
        $this->app->singleton(DashboardService::class);
        $this->app->singleton(WebhookSignatureValidator::class);
        $this->app->singleton(WebhookHandler::class);

        $this->app->singleton('whatsapp', function ($app) {
            return new WhatsAppManager(
                $app->make(ProviderFactory::class),
                $app->make(AccountResolverInterface::class),
                $app->make(MessageLoggerInterface::class),
                $app->make(ConversationRecorderInterface::class),
            );
        });

        $this->app->alias('whatsapp', WhatsAppManager::class);
        $this->app->bind(WhatsAppChannel::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/whatsapp.php' => config_path('whatsapp.php'),
            ], 'whatsapp-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'whatsapp-migrations');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/whatsapp'),
            ], 'whatsapp-views');

            $this->commands([
                InstallCommand::class,
                TestCommand::class,
            ]);
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'whatsapp');
        $this->loadRoutesFrom(__DIR__.'/../routes/webhook.php');

        if (config('whatsapp.admin.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/admin.php');
        }
    }
}
