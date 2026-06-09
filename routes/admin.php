<?php

use Illuminate\Support\Facades\Route;
use Vendor\LaravelWhatsAppCloud\Http\Controllers\Admin\AccountController;
use Vendor\LaravelWhatsAppCloud\Http\Controllers\Admin\CampaignController;
use Vendor\LaravelWhatsAppCloud\Http\Controllers\Admin\ContactController;
use Vendor\LaravelWhatsAppCloud\Http\Controllers\Admin\ConversationController;
use Vendor\LaravelWhatsAppCloud\Http\Controllers\Admin\DashboardController;
use Vendor\LaravelWhatsAppCloud\Http\Controllers\Admin\SystemController;
use Vendor\LaravelWhatsAppCloud\Http\Controllers\Admin\TemplateController;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppContact;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppConversation;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppTag;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppTemplate;

$prefix = config('whatsapp.admin.prefix', 'admin/whatsapp');
$middleware = config('whatsapp.admin.middleware', ['web']);

Route::middleware($middleware)
    ->prefix($prefix)
    ->name('whatsapp.admin.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/system', SystemController::class)->name('system');

        Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
        Route::get('/accounts/create', [AccountController::class, 'create'])->name('accounts.create');
        Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
        Route::get('/accounts/{account}', [AccountController::class, 'show'])->name('accounts.show');
        Route::get('/accounts/{account}/edit', [AccountController::class, 'edit'])->name('accounts.edit');
        Route::put('/accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');
        Route::delete('/accounts/{account}', [AccountController::class, 'destroy'])->name('accounts.destroy');
        Route::post('/accounts/{account}/default', [AccountController::class, 'setDefault'])->name('accounts.default');
        Route::post('/accounts/{account}/toggle', [AccountController::class, 'toggleActive'])->name('accounts.toggle');
        Route::post('/accounts/{account}/test', [AccountController::class, 'sendTest'])->name('accounts.test');
        Route::post('/accounts/{account}/sync-business', [AccountController::class, 'syncBusiness'])->name('accounts.sync-business');
        Route::post('/accounts/{account}/sync-numbers', [AccountController::class, 'syncNumbers'])->name('accounts.sync-numbers');

        Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
        Route::get('/contacts/{contact}', [ContactController::class, 'show'])->name('contacts.show');
        Route::post('/contacts/{contact}/notes', [ContactController::class, 'storeNote'])->name('contacts.notes.store');
        Route::post('/contacts/{contact}/tags/create', [ContactController::class, 'storeTag'])->name('contacts.tags.create');
        Route::post('/contacts/{contact}/tags', [ContactController::class, 'syncTags'])->name('contacts.tags.sync');
        Route::delete('/contacts/{contact}/tags/{tag}', [ContactController::class, 'detachTag'])->name('contacts.tags.detach');

        Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
        Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
        Route::post('/conversations/{conversation}/reply', [ConversationController::class, 'reply'])->name('conversations.reply');

        Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
        Route::get('/campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
        Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');

        Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
        Route::get('/templates/{template}', [TemplateController::class, 'show'])->name('templates.show');
        Route::post('/templates/sync', [TemplateController::class, 'sync'])->name('templates.sync');
    });

Route::bind('account', fn (string $value) => WhatsAppAccount::query()->findOrFail($value));
Route::bind('contact', fn (string $value) => WhatsAppContact::query()->findOrFail($value));
Route::bind('conversation', fn (string $value) => WhatsAppConversation::query()->findOrFail($value));
Route::bind('tag', fn (string $value) => WhatsAppTag::query()->findOrFail($value));
Route::bind('template', fn (string $value) => WhatsAppTemplate::query()->findOrFail($value));
