<?php

namespace Database\Seeders;

use App\Models\Admin\Hr\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeeUserSeeder extends Seeder
{
    public function run(): void
    {
        // Clean up old employee-type users (keep admin + students)
        User::where('type', 'employee')->delete();

        $employees = Employee::all();
        $bar = $this->command->getOutput()->createProgressBar($employees->count());
        $bar->start();

        foreach ($employees as $employee) {
            $email = $employee->employee_code . '@rowad.app';

            $user = User::create([
                'name' => $employee->first_name_ar . ' ' . $employee->last_name_ar,
                'email' => $email,
                'password' => Hash::make('password'),
                'is_active' => true,
                'type' => 'employee',
            ]);

            $employee->update(['user_id' => $user->id]);

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info('تم إنشاء ' . $employees->count() . ' مستخدم وربطهم بالموظفين. كلمة المرور: password');
    }
}
