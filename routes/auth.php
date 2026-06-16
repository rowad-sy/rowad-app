<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    // Choose user type first
    Volt::route('choose', 'auth.choose')
        ->name('auth.choose');

    // Redirect old /login and /register to /choose
    Route::redirect('login', '/choose');
    Route::redirect('register', '/choose');

    // Beneficiary login
    Volt::route('login/beneficiary', 'auth.login-beneficiary')
        ->name('login.beneficiary');

    // Employee login
    Volt::route('login/employee', 'auth.login-employee')
        ->name('login.employee');

    // Employee registration (with center/project)
    Volt::route('register/employee', 'auth.register-employee')
        ->name('register.employee');

    // Password reset (kept as original)
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

Route::post('logout', App\Livewire\Actions\Logout::class)
    ->name('logout');
