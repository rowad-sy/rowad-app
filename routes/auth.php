<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    // Single unified login page — detects user type and redirects accordingly
    Volt::route('login', 'auth.login')
        ->name('login');

    // Choose action (login only — registration is handled by admins)
    Volt::route('choose', 'auth.choose')
        ->name('auth.choose');

    // Redirect old separate login URLs to unified login
    Route::redirect('login/employee', '/login');
    Route::redirect('login/beneficiary', '/login');
    Route::redirect('register', '/login');
    Route::redirect('register/employee', '/login');

    // Password reset
    Volt::route('forgot-password', 'auth.forgot-password')
        ->name('password.request');

    Volt::route('reset-password/{token}', 'auth.reset-password')
        ->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Volt::route('verify-email', 'auth.verify-email')
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Volt::route('confirm-password', 'auth.confirm-password')
        ->name('password.confirm');
});

// Generic authenticated landing page and account settings (Flux starter-kit surface)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')
        ->name('settings.profile');

    Volt::route('settings/password', 'settings.password')
        ->name('settings.password');

    Volt::route('settings/appearance', 'settings.appearance')
        ->name('settings.appearance');
});

Route::post('logout', App\Livewire\Actions\Logout::class)
    ->name('logout');
