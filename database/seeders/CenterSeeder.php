<?php

namespace Database\Seeders;

use App\Models\Admin\Center;
use Illuminate\Database\Seeder;

class CenterSeeder extends Seeder
{
    public function run(): void
    {
        $centers = [
            ['name' => 'عفرين', 'address' => 'عفرين - سوريا', 'phone' => ''],
            ['name' => 'الباب', 'address' => 'الباب - سوريا', 'phone' => ''],
            ['name' => 'الأتارب', 'address' => 'الأتارب - سوريا', 'phone' => ''],
            ['name' => 'حلب', 'address' => 'حلب - سوريا', 'phone' => ''],
            ['name' => 'اعزاز', 'address' => 'اعزاز - سوريا', 'phone' => ''],
            ['name' => 'جرابلس', 'address' => 'جرابلس - سوريا', 'phone' => ''],
            ['name' => 'جنديرس', 'address' => 'جنديرس - سوريا', 'phone' => ''],
            ['name' => 'مارع', 'address' => 'مارع - سوريا', 'phone' => ''],
            ['name' => 'نيزب', 'address' => 'نيزب - سوريا', 'phone' => ''],
            ['name' => 'سلقين', 'address' => 'سلقين - سوريا', 'phone' => ''],
        ];

        foreach ($centers as $center) {
            Center::create($center);
        }
    }
}
