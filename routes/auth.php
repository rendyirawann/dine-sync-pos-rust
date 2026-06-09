<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // --- REGISTER ---
    // Jika aplikasi perizinan tidak boleh daftar sendiri (hanya admin yg buat user),
    // Abang bisa matikan (komentar) 2 baris route register di bawah ini.
    Route::get('/admin/register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    // --- LOGIN ---
    Route::get('/admin/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    // PENTING: Saya tambahkan middleware throttle (Limit 5x percobaan per menit)
    // Ini menggantikan fungsi yang tadi dihapus di web.php
    Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:3,1');

    // --- FORGOT PASSWORD ---
    Route::get('/admin/forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('/admin/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    // --- RESET PASSWORD ---
    Route::get('/admin/reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('/admin/reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    // --- VERIFY EMAIL ---
    Route::get('/admin/verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('/admin/verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('/admin/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // --- CONFIRM PASSWORD ---
    Route::get('/admin/confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('/admin/confirm-password', [ConfirmablePasswordController::class, 'store']);

    // --- UPDATE PASSWORD ---
    Route::put('/admin/password', [PasswordController::class, 'update'])->name('password.update');

    // --- LOGOUT ---
    Route::post('/admin/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
