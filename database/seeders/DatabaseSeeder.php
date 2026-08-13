<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /*
    * Seeder الرئيسي - ينشئ مستخدم السوبر أدمن فقط
    * يتم تشغيله بأمر: php artisan db:seed
    */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'omar.elnayif@onder1.org'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'is_active' => true,
                'type' => 'super-admin',
            ]
        );
    }
}
