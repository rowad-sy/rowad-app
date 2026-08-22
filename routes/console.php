<?php

use App\Services\AuditAnchorService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(\Illuminate\Foundation\Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::call(function () {
    AuditAnchorService::createAnchor();
})->daily()->at('02:00')->description('Create daily audit log anchor hash');
