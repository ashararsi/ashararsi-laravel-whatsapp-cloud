<?php

use Illuminate\Support\Facades\Route;
use Vendor\LaravelWhatsAppCloud\Http\Controllers\Admin\AccountController;
use Vendor\LaravelWhatsAppCloud\Http\Controllers\Admin\ContactController;
use Vendor\LaravelWhatsAppCloud\Http\Controllers\Admin\ConversationController;
use Vendor\LaravelWhatsAppCloud\Http\Controllers\Admin\DashboardController;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppContact;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppConversation;

$prefix = config('whatsapp.admin.prefix', 'admin/whatsapp');
$middleware = config('whatsapp.admin.middleware', ['web']);

Route::middleware($middleware)
    ->prefix($prefix)
    ->name('whatsapp.admin.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

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

        Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
        Route::get('/contacts/{contact}', [ContactController::class, 'show'])->name('contacts.show');

        Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
        Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    });

Route::bind('account', fn (string $value) => WhatsAppAccount::query()->findOrFail($value));
Route::bind('contact', fn (string $value) => WhatsAppContact::query()->findOrFail($value));
Route::bind('conversation', fn (string $value) => WhatsAppConversation::query()->findOrFail($value));
