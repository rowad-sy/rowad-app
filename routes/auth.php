<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    // Single unified login page — detects user type and redirects accordingly
    Volt::route('login', 'auth.login')
        ->name('login');

    // Choose user type (for registration)
    Volt::route('choose', 'auth.choose')
        ->name('auth.choose');

    // Redirect old separate login URLs to unified login
    Route::redirect('login/employee', '/login');
    Route::redirect('login/beneficiary', '/login');

    // Redirect old /register to /choose
    Route::redirect('register', '/choose');

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
