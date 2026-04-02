<?php

use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Settings\AdminPhoneSettingsController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/webhook/whatsapp', [WhatsAppWebhookController::class, 'verify']);
Route::post('/webhook/whatsapp', [WhatsAppWebhookController::class, 'handle']);

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [MonitoringController::class, 'dashboard'])->name('dashboard');
    Route::get('/conversations', [MonitoringController::class, 'index'])->name('conversations.index');
    Route::get('/conversations/{conversation}', [MonitoringController::class, 'show'])->name('conversations.show');
    Route::post('/conversations/{conversation}/send-text', [MonitoringController::class, 'sendText'])->name('conversations.send-text');
    Route::post('/conversations/{conversation}/send-media', [MonitoringController::class, 'sendMedia'])->name('conversations.send-media');
    Route::post('/conversations/{conversation}/send-template', [MonitoringController::class, 'sendTemplate'])->name('conversations.send-template');
    Route::patch('/conversations/{conversation}/assign', [MonitoringController::class, 'assign'])
        ->middleware('role:admin')
        ->name('conversations.assign');

    Route::middleware('role:admin')->group(function () {
        Route::get('/settings/admin-phones', [AdminPhoneSettingsController::class, 'edit'])->name('settings.admin-phones');
        Route::put('/settings/admin-phones', [AdminPhoneSettingsController::class, 'update'])->name('settings.admin-phones.update');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
