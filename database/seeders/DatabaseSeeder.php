<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /*
    * Seeder الرئيسي - يستدعي جميع Seeders النظام
    * يتم تشغيله بأمر: php artisan db:seed
    */
    public function run(): void
    {
        $this->call([
            CenterSeeder::class,
            ProjectSeeder::class,
            DepartmentSeeder::class,
        ]);

        if (!User::where('email', 'admin@rowad.app')->exists()) {
            User::factory()->create([
                'name' => 'مدير النظام',
                'email' => 'admin@rowad.app',
                'password' => bcrypt('admin123'),
                'is_active' => true,
            ]);
        }

        $this->call([
            GroupSeeder::class,
        ]);

        $this->call([
            EmployeeSeeder::class,
        ]);

        $this->call([
            EmployeeUserSeeder::class,
        ]);

        $this->call([
            StudentSeeder::class,
        ]);

        $this->call([
            CoursePeriodSeeder::class,
        ]);
    }
}
