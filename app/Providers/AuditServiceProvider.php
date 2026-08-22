<?php

namespace App\Providers;

use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(Login::class, function (Login $event) {
            AuditLogger::recordEvent(
                modelClass: \App\Models\User::class,
                modelId: $event->user->id,
                event: 'login',
                description: 'تسجيل الدخول',
                model: $event->user,
            );
        });

        Event::listen(Logout::class, function (Logout $event) {
            AuditLogger::recordEvent(
                modelClass: \App\Models\User::class,
                modelId: $event->user?->id,
                event: 'logout',
                description: 'تسجيل الخروج',
                model: $event->user,
            );
        });

        Event::listen(Failed::class, function (Failed $event) {
            AuditLogger::recordEvent(
                modelClass: \App\Models\User::class,
                modelId: null,
                event: 'failed',
                description: 'محاولة دخول فاشلة: ' . $event->credentials['email'] ?? 'غير معروف',
            );
        });
    }
}
