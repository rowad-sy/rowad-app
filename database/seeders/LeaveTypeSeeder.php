<?php

namespace Database\Seeders;

use App\Models\Admin\Hr\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name_ar' => 'إجازة سنوية',
                'annual_days' => 21,
                'requires_approval' => true,
                'approver_ids' => [],
                'color' => '#0d6efd',
                'icon' => 'bi-sun',
                'is_active' => true,
            ],
            [
                'name_ar' => 'إجازة مرضية',
                'annual_days' => 14,
                'requires_approval' => false,
                'approver_ids' => [],
                'color' => '#dc3545',
                'icon' => 'bi-heart-pulse',
                'is_active' => true,
            ],
            [
                'name_ar' => 'إجازة طارئة',
                'annual_days' => 7,
                'requires_approval' => true,
                'approver_ids' => [],
                'color' => '#fd7e14',
                'icon' => 'bi-exclamation-triangle',
                'is_active' => true,
            ],
            [
                'name_ar' => 'إجازة أمومة',
                'annual_days' => 90,
                'requires_approval' => true,
                'approver_ids' => [],
                'color' => '#e83e8c',
                'icon' => 'bi-gender-female',
                'is_active' => true,
            ],
            [
                'name_ar' => 'إجازة بدون راتب',
                'annual_days' => 30,
                'requires_approval' => true,
                'approver_ids' => [],
                'color' => '#6c757d',
                'icon' => 'bi-currency-exchange',
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            LeaveType::firstOrCreate(
                ['name_ar' => $type['name_ar']],
                $type
            );
        }
    }
}
