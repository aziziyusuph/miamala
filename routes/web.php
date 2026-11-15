<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusinessSetupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.landing')->name('landing');

Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
    ->name('verification.notice');
Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
    ->middleware('throttle:verification-resend')
    ->name('verification.send');
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->whereNumber('id')
    ->name('verification.verify');

Route::get('/onboarding/resume', [EmailVerificationController::class, 'notice'])
    ->name('onboarding.resume');

Route::middleware('business.setup')->group(function () {
    Route::get('/onboarding/business', [BusinessSetupController::class, 'create'])
        ->name('business.setup');
    Route::post('/onboarding/business', [BusinessSetupController::class, 'store'])
        ->middleware('throttle:business-setup')
        ->name('business.setup.store');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])
        ->middleware('throttle:login-session')
        ->name('login.store');
    Route::get('/register', [AuthController::class, 'createRegistration'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:registration')
        ->name('register.store');

    Route::get('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'resetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'verified', 'has.business'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/transactions/export', [TransactionController::class, 'export'])
        ->name('transactions.export');
    Route::post('/transactions/{transaction}/reconcile', [TransactionController::class, 'reconcile'])
        ->name('transactions.reconcile');
    Route::resource('transactions', TransactionController::class);
});
