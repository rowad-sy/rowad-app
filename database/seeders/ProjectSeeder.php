<?php

namespace Database\Seeders;

use App\Models\Admin\Center;
use App\Models\Admin\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $centers = Center::all();

        $projects = [
            ['name' => 'رواد العلم', 'description' => 'مشروع تعليمي لدعم الطلاب في المواد العلمية'],
            ['name' => 'أثر', 'description' => 'مشروع التنمية المجتمعية والأثر الاجتماعي'],
            ['name' => 'العلم نور', 'description' => 'مشروع محو الأمية وتعليم الكبار'],
            ['name' => 'رواد المعرفة', 'description' => 'مشروع المكتبات وتشجيع القراءة'],
            ['name' => 'الرواد المتفوقين', 'description' => 'مشروع رعاية الطلاب المتفوقين'],
            ['name' => 'الرواد الصغار', 'description' => 'مشروع رعاية الأطفال والتعليم المبكر'],
            ['name' => 'أهل القرآن', 'description' => 'مشروع تحفيظ القرآن الكريم وتعليمه'],
            ['name' => 'معهد الرواد للتطوير الإداري والابتكاري', 'description' => 'معهد متخصص في التطوير الإداري والابتكار'],
            ['name' => 'اصرار', 'description' => 'مشروع دعم ذوي الاحتياجات الخاصة'],
            ['name' => 'معهد الرواد للعلوم التقنية', 'description' => 'معهد متخصص في تعليم العلوم التقنية والمهنية'],
            ['name' => 'معهد الرواد للتدريب والتأهيل المهني', 'description' => 'معهد متخصص في التدريب والتأهيل المهني'],
            ['name' => 'قدرات', 'description' => 'مشروع تنمية المهارات والقدرات الشخصية'],
            ['name' => 'الرعاية المجتمعية', 'description' => 'مشروع الرعاية الاجتماعية والدعم المجتمعي'],
            ['name' => 'معهد الرواد للثقافة والتراث والفنون', 'description' => 'معهد متخصص في الثقافة والتراث والفنون'],
            ['name' => 'لعبة وفرحة', 'description' => 'مشروع الأنشطة الترفيهية للأطفال'],
            ['name' => 'الرعاية الصحية', 'description' => 'مشروع الرعاية الصحية والدعم الطبي'],
        ];

        foreach ($projects as $data) {
            $project = Project::create($data);
            $project->centers()->attach(
                $centers->random(min(3, $centers->count()))->pluck('id')->toArray()
            );
        }
    }
}
