<?php

namespace Vendor\LaravelWhatsAppCloud;

use Filament\Panel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Vendor\LaravelWhatsAppCloud\Commands\DoctorCommand;
use Vendor\LaravelWhatsAppCloud\Commands\InstallCommand;
use Vendor\LaravelWhatsAppCloud\Commands\RunCampaignsCommand;
use Vendor\LaravelWhatsAppCloud\Commands\SendScheduledMessagesCommand;
use Vendor\LaravelWhatsAppCloud\Commands\SyncBusinessCommand;
use Vendor\LaravelWhatsAppCloud\Commands\SyncNumbersCommand;
use Vendor\LaravelWhatsAppCloud\Commands\SyncTemplatesCommand;
use Vendor\LaravelWhatsAppCloud\Commands\TestCommand;
use Vendor\LaravelWhatsAppCloud\Contracts\AccountResolverInterface;
use Vendor\LaravelWhatsAppCloud\Contracts\ConversationRecorderInterface;
use Vendor\LaravelWhatsAppCloud\Contracts\MessageLoggerInterface;
use Vendor\LaravelWhatsAppCloud\Contracts\WhatsAppClientInterface;
use Vendor\LaravelWhatsAppCloud\Events\MessageReceived;
use Vendor\LaravelWhatsAppCloud\Filament\WhatsAppFilamentPlugin;
use Vendor\LaravelWhatsAppCloud\Listeners\LogIncomingMessage;
use Vendor\LaravelWhatsAppCloud\Listeners\ProcessIncomingMessage;
use Vendor\LaravelWhatsAppCloud\Notifications\WhatsAppChannel;
use Vendor\LaravelWhatsAppCloud\Services\AccountResolver;
use Vendor\LaravelWhatsAppCloud\Services\AiAutoReplyEngine;
use Vendor\LaravelWhatsAppCloud\Services\AnalyticsService;
use Vendor\LaravelWhatsAppCloud\Services\AudioTranscriptionService;
use Vendor\LaravelWhatsAppCloud\Services\AutoReplyEngine;
use Vendor\LaravelWhatsAppCloud\Services\BusinessProfileSyncService;
use Vendor\LaravelWhatsAppCloud\Services\CampaignService;
use Vendor\LaravelWhatsAppCloud\Services\ConversationService;
use Vendor\LaravelWhatsAppCloud\Services\DashboardService;
use Vendor\LaravelWhatsAppCloud\Services\GraphApiClient;
use Vendor\LaravelWhatsAppCloud\Services\MediaDownloadService;
use Vendor\LaravelWhatsAppCloud\Services\MediaUploadService;
use Vendor\LaravelWhatsAppCloud\Services\MessageLogger;
use Vendor\LaravelWhatsAppCloud\Services\OpenAiService;
use Vendor\LaravelWhatsAppCloud\Services\PhoneNumberSyncService;
use Vendor\LaravelWhatsAppCloud\Services\ProviderFactory;
use Vendor\LaravelWhatsAppCloud\Services\ScheduledMessageService;
use Vendor\LaravelWhatsAppCloud\Services\SystemHealthService;
use Vendor\LaravelWhatsAppCloud\Services\TemplateSyncService;
use Vendor\LaravelWhatsAppCloud\Services\TenantContext;
use Vendor\LaravelWhatsAppCloud\Services\TwilioSignatureValidator;
use Vendor\LaravelWhatsAppCloud\Services\TwilioWebhookHandler;
use Vendor\LaravelWhatsAppCloud\Services\WebhookHandler;
use Vendor\LaravelWhatsAppCloud\Services\WebhookSignatureValidator;
use Vendor\LaravelWhatsAppCloud\Services\WhatsAppClient;
use Vendor\LaravelWhatsAppCloud\Services\WhatsAppManager;
use Vendor\LaravelWhatsAppCloud\Services\WhatsAppSettingsService;
use Vendor\LaravelWhatsAppCloud\Services\WorkflowEngine;

class WhatsAppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/whatsapp.php', 'whatsapp');

        $this->app->singleton(GraphApiClient::class);
        $this->app->singleton(WhatsAppClientInterface::class, WhatsAppClient::class);
        $this->app->singleton(ProviderFactory::class);
        $this->app->singleton(MediaUploadService::class);
        $this->app->singleton(BusinessProfileSyncService::class);
        $this->app->singleton(PhoneNumberSyncService::class);
        $this->app->singleton(SystemHealthService::class);
        $this->app->singleton(TenantContext::class);
        $this->app->singleton(WhatsAppSettingsService::class);
        $this->app->singleton(AccountResolverInterface::class, AccountResolver::class);
        $this->app->singleton(MessageLoggerInterface::class, MessageLogger::class);
        $this->app->singleton(ConversationRecorderInterface::class, ConversationService::class);
        $this->app->singleton(ConversationService::class);
        $this->app->singleton(DashboardService::class);
        $this->app->singleton(AnalyticsService::class);
        $this->app->singleton(OpenAiService::class);
        $this->app->singleton(MediaDownloadService::class);
        $this->app->singleton(AudioTranscriptionService::class);
        $this->app->singleton(AiAutoReplyEngine::class);
        $this->app->singleton(AutoReplyEngine::class);
        $this->app->singleton(WorkflowEngine::class);
        $this->app->singleton(CampaignService::class);
        $this->app->singleton(ScheduledMessageService::class);
        $this->app->singleton(TemplateSyncService::class);
        $this->app->singleton(WebhookSignatureValidator::class);
        $this->app->singleton(TwilioSignatureValidator::class);
        $this->app->singleton(WebhookHandler::class);
        $this->app->singleton(TwilioWebhookHandler::class);

        $this->app->singleton('whatsapp', function ($app) {
            return new WhatsAppManager(
                $app->make(ProviderFactory::class),
                $app->make(AccountResolverInterface::class),
                $app->make(MessageLoggerInterface::class),
                $app->make(ConversationRecorderInterface::class),
                $app->make(MediaUploadService::class),
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
                DoctorCommand::class,
                SyncTemplatesCommand::class,
                SyncBusinessCommand::class,
                SyncNumbersCommand::class,
                RunCampaignsCommand::class,
                SendScheduledMessagesCommand::class,
            ]);
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'whatsapp');
        $this->loadRoutesFrom(__DIR__.'/../routes/webhook.php');

        $this->app->booted(function () {
            $this->app->make(WhatsAppSettingsService::class)->applyToConfig();
        });

        if (config('whatsapp.admin.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/admin.php');
        }

        $this->registerEventListeners();
        $this->registerFilament();
    }

    protected function registerEventListeners(): void
    {
        if (config('whatsapp.events.log_incoming', true)) {
            Event::listen(MessageReceived::class, LogIncomingMessage::class);
        }

        if (config('whatsapp.events.process_incoming', true)) {
            Event::listen(MessageReceived::class, ProcessIncomingMessage::class);
        }
    }

    protected function registerFilament(): void
    {
        if (! config('whatsapp.filament.enabled', true)) {
            return;
        }

        if (class_exists(Panel::class) && class_exists(WhatsAppFilamentPlugin::class)) {
            // Host app registers: ->plugin(\Vendor\LaravelWhatsAppCloud\Filament\WhatsAppFilamentPlugin::make())
        }
    }
}
