<?php

namespace Database\Seeders;

/*
 * سيدر محلي مخصص للتجربة فقط — لا يُضاف إلى DatabaseSeeder الرئيسي.
 * يُشغّل يدوياً: php artisan db:seed --class=ScenarioDemoSeeder
 *
 * ينشئ مشاريع/مستويات/مواد/مدرّسين/خطط تدريبية لسيناريوهات:
 *  - الروضة (الرواد الصغار)
 *  - رواد العلم (صفوف 1-8)
 *  - أثر (صفوف 9-12، علمي/أدبي)
 *  - معهد التدريب والتأهيل المهني (مستويات تدريب)
 *  - معهد التطوير الإداري والابتكار (دورات إدارية/دبلومة)
 */

use App\Models\Admin\Cohort;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\Project;
use App\Models\Admin\Student\AcademicLevel;
use App\Models\Admin\Student\Course;
use App\Models\Admin\Student\LevelSubjectInstructor;
use App\Models\Admin\Student\Subject;
use App\Models\Admin\Student\TrainingPlan;
use App\Models\Admin\Student\TrainingPlanLesson;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScenarioDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $teacher = function (string $first, string $last) {
                $base = 'DEMO-';
                foreach (['', 'A', 'B', 'C', 'D', 'E'] as $suffix) {
                    $code = $base . $first . $last . $suffix;
                    if (!Employee::where('employee_code', $code)->exists()) {
                        return Employee::firstOrCreate(
                            ['first_name_ar' => $first, 'last_name_ar' => $last],
                            ['employee_code' => $code, 'gender' => 'male', 'status' => 'active']
                        );
                    }
                }
                throw new \Exception("Cannot generate unique employee_code for {$first} {$last}");
            };

            // ── المعلّمون الوهميون ──
            $ahemd  = $teacher('أحمد', 'الخطيب');
            $moham  = $teacher('محمد', 'السيد');
            $khaled = $teacher('خالد', 'عبدالله');
            $sara   = $teacher('سارة', 'نور');
            $omar   = $teacher('عمر', 'الحلبي');
            $lina   = $teacher('لينا', 'صالح');
            $huda   = $teacher('هدى', 'رمضان');
            $nadia  = $teacher('نادية', 'شحادة');

            // ── المشاريع الخمسة ──
            $kindergarten = $this->project('الروضة - الرواد الصغار', 'رياض أطفال');
            $rowadScience = $this->project('رواد العلم', 'صفوف 1-8');
            $athar        = $this->project('أثر', 'طلاب منقطعون عن الدراسة');
            $vocational   = $this->project('معهد الرواد للتدريب والتأهيل المهني', 'تدريب مهني');
            $adminInno    = $this->project('معهد الرواد للتطوير الإداري والابتكار', 'دورات إدارية');

            // ═══════════════ 1) الروضة ═══════════════
            $kgSubjects = $this->subjects(['حساب', 'لغة عربية', 'لغة إنكليزية', 'أخلاق', 'رسم', 'أنشطة']);

            $kg2 = $this->level($kindergarten, 'الطفولة الثانية', 'طفولة', 1);
            $kg3 = $this->level($kindergarten, 'الطفولة الثالثة', 'طفولة', 2);

            // مدرّس الصف (سارة) يعلّم كل المواد لكل مستوى
            foreach ([$kg2, $kg3] as $lv) {
                $sortIdx = 0;
                foreach ($kgSubjects as $subject) {
                    $lv->subjects()->syncWithoutDetaching([$subject->id => ['sort_order' => $sortIdx++]]);
                    LevelSubjectInstructor::firstOrCreate([
                        'academic_level_id' => $lv->id,
                        'subject_id' => $subject->id,
                        'instructor_id' => $sara->id,
                    ], ['is_main' => true]);
                }
            }
            // مواد مشتركة لكل الصفوف يدرّسها متخصّص: إنكليزي=أحمد، رسم/أنشطة=لينا
            foreach ([$kg2, $kg3] as $lv) {
                foreach (['لغة إنكليزية' => $ahemd, 'رسم' => $lina, 'أنشطة' => $lina] as $sName => $ins) {
                    LevelSubjectInstructor::firstOrCreate([
                        'academic_level_id' => $lv->id,
                        'subject_id' => $kgSubjects[$sName]->id,
                        'instructor_id' => $ins->id,
                    ]);
                }
            }

            // أفواج الروضة: صباحي ومسائي (مسؤول الفوج = مدرّسة الروضة سارة)
            $this->cohort($kindergarten, $sara, 'الفوج الصباحي', 'صباحي', 'KD-M');
            $this->cohort($kindergarten, $sara, 'الفوج المسائي', 'مسائي', 'KD-E');

            // ═══════════════ 2) رواد العلم (1-8) ═══════════════
            $rsSubjects = $this->subjects(['حساب', 'لغة عربية', 'لغة إنكليزية', 'علوم', 'دراسات اجتماعية']);
            $rowadLevels = [];
            for ($g = 1; $g <= 8; $g++) {
                $rowadLevels[$g] = $this->level($rowadScience, "الصف {$g}", 'grade', $g);
                $sortIdx = 0;
                foreach ($rsSubjects as $subject) {
                    $rowadLevels[$g]->subjects()->syncWithoutDetaching([$subject->id => ['sort_order' => $sortIdx++]]);
                    $instructor = [1 => $ahemd, 2 => $omar, 0 => $khaled][$g % 3];
                    LevelSubjectInstructor::firstOrCreate([
                        'academic_level_id' => $rowadLevels[$g]->id,
                        'subject_id' => $subject->id,
                        'instructor_id' => $instructor->id,
                    ], ['is_main' => true]);
                }
            }

            // ═══════════════ 3) أثر (9-12، علمي/أدبي) ═══════════════
            $sciCourse = $this->course($athar, 'الثانوية العلمية');
            $litCourse = $this->course($athar, 'الثانوية الأدبية');
            $sciSubjects = $this->subjects(['رياضيات', 'فيزياء', 'كيمياء', 'عربي', 'إنكليزي']);
            $litSubjects = $this->subjects(['عربي', 'جغرافيا', 'تاريخ', 'إنكليزي']);

            // خريطة مدرّسين للمواد
            $sciInstructor = ['رياضيات' => [$ahemd, $khaled], 'فيزياء' => [$omar], 'كيمياء' => [$nadia], 'عربي' => [$lina], 'إنكليزي' => [$moham]];
            $litInstructor = ['عربي' => [$lina], 'جغرافيا' => [$huda], 'تاريخ' => [$huda], 'إنكليزي' => [$moham]];

            foreach ([9, 10, 11, 12] as $g) {
                // علمي
                $lvSci = $this->level($athar, "الصف {$g} - علمي", 'grade', $g, "علمي-{$g}");
                $sortIdx = 0;
                foreach ($sciSubjects as $subject) {
                    $subject->course_id = $sciCourse->id; $subject->save();
                    $lvSci->subjects()->syncWithoutDetaching([$subject->id => ['sort_order' => $sortIdx++]]);
                    foreach ($sciInstructor[$subject->name_ar] as $ins) {
                        LevelSubjectInstructor::firstOrCreate([
                            'academic_level_id' => $lvSci->id,
                            'subject_id' => $subject->id,
                            'instructor_id' => $ins->id,
                        ], ['is_main' => $ins->id === $sciInstructor[$subject->name_ar][0]->id]);
                    }
                }

                // أدبي
                $lvLit = $this->level($athar, "الصف {$g} - أدبي", 'grade', $g, "أدبي-{$g}");
                $sortIdx = 0;
                foreach ($litSubjects as $subject) {
                    $subject->course_id = $litCourse->id; $subject->save();
                    $lvLit->subjects()->syncWithoutDetaching([$subject->id => ['sort_order' => $sortIdx++]]);
                    foreach ($litInstructor[$subject->name_ar] as $ins) {
                        LevelSubjectInstructor::firstOrCreate([
                            'academic_level_id' => $lvLit->id,
                            'subject_id' => $subject->id,
                            'instructor_id' => $ins->id,
                        ], ['is_main' => $ins->id === $litInstructor[$subject->name_ar][0]->id]);
                    }
                }
            }

            // ═══════════════ 4) معهد التدريب المهني (مستويات) ═══════════════
            $khayata  = $this->course($vocational, 'الخياطة');
            $khayataS = $this->subjects(['خياطة أول', 'خياطة ثاني', 'خياطة ثالث']);
            $kL = [];
            $khayataSList = array_values($khayataS);
            foreach ([1 => 0, 2 => 1, 3 => 2] as $lvNo => $sIdx) {
                $lv = $this->level($vocational, "الخياطة - مستوى {$lvNo}", 'level', $lvNo);
                $kL[$lvNo] = $lv;
                $sub = $khayataSList[$sIdx]; $sub->course_id = $khayata->id; $sub->save();
                $lv->subjects()->syncWithoutDetaching([$sub->id => ['sort_order' => 0]]);
                LevelSubjectInstructor::firstOrCreate([
                    'academic_level_id' => $lv->id, 'subject_id' => $sub->id, 'instructor_id' => $nadia->id,
                ], ['is_main' => true]);
            }
            // تدريبات أخرى:
            $this->subjectCourseAttach($this->course($vocational, 'الكوافيرة'), $this->subjects(['كوافيرة أول', 'كوافيرة ثاني']), [$nadia]);
            $this->subjectCourseAttach($this->course($vocational, 'الصوف والكروشيه'), $this->subjects(['صوف وكروشيه أول']), [$huda]);
            $this->subjectCourseAttach($this->course($vocational, 'الإيتامين'), $this->subjects(['إيتامين أول', 'إيتامين ثاني']), [$omar]);

            // ═══════════════ 5) معهد التطوير الإداري والابتكار (دبلومة) ═══════════════
            $admCourse = $this->course($adminInno, 'دبلومة إدارة الموارد البشرية');
            $admSubj = $this->subjects(['إدارة الأعمال', 'إدارة الموارد البشرية', 'محاسبة']);
            foreach ($admSubj as $subject) { $subject->course_id = $admCourse->id; $subject->save(); }
            $admL = [];
            foreach ([1, 2] as $lvNo) {
                $lv = $this->level($adminInno, "دبلومة الموارد البشرية - مستوى {$lvNo}", 'level', $lvNo);
                $admL[$lvNo] = $lv;
                $sortIdx = 0;
                foreach ($admSubj as $subject) {
                    $subject->course_id = $admCourse->id; $subject->save();
                    $lv->subjects()->syncWithoutDetaching([$subject->id => ['sort_order' => $sortIdx++]]);
                }
                LevelSubjectInstructor::firstOrCreate([
                    'academic_level_id' => $lv->id, 'subject_id' => $admSubj['إدارة الأعمال']->id, 'instructor_id' => $ahemd->id,
                ], ['is_main' => true]);
                LevelSubjectInstructor::firstOrCreate([
                    'academic_level_id' => $lv->id, 'subject_id' => $admSubj['إدارة الموارد البشرية']->id, 'instructor_id' => $moham->id,
                ], ['is_main' => true]);
                LevelSubjectInstructor::firstOrCreate([
                    'academic_level_id' => $lv->id, 'subject_id' => $admSubj['محاسبة']->id, 'instructor_id' => $nadia->id,
                ], ['is_main' => true]);
            }

            // ═══════════════ خطط تدريبية (دورات) ═══════════════
            $plan1 = $this->plan($adminInno, 'خطة دبلومة الموارد البشرية', 3);
            $this->fillWeeklyLessons($plan1, array_values($admL), array_values($admSubj));

            $plan2 = $this->plan($vocational, 'خطة الخياطة', 3);
            $this->fillWeeklyLessons($plan2, array_values($kL), array_values($khayataS));

            $mathLevel = $this->level($athar, "الصف 12 - علمي", 'grade', 12, 'علمي-12');
            $plan3 = $this->plan($athar, 'خطة الثاني عشر العلمي', 6);
            $this->fillWeeklyLessons($plan3, [$mathLevel], array_values($sciSubjects));

            fwrite(STDOUT, "Scenario demo data created.\n");
        });
    }

    private function project(string $name, string $desc): Project
    {
        return Project::firstOrCreate(['name' => $name], ['description' => $desc]);
    }

    private function course(Project $project, string $name): Course
    {
        return Course::firstOrCreate(['name_ar' => $name], ['project_id' => $project->id]);
    }

    private function subjects(array $names): array
    {
        $result = [];
        foreach (array_values($names) as $i => $name) {
            $result[$name] = Subject::firstOrCreate(['name_ar' => $name], ['sort_order' => $i]);
        }
        return $result;
    }

    private function level(Project $project, string $name, string $type, int $sort, ?string $code = null): AcademicLevel
    {
        return AcademicLevel::firstOrCreate(
            ['project_id' => $project->id, 'name_ar' => $name],
            ['type' => $type, 'sort_order' => $sort, 'code' => $code]
        );
    }

    private function plan(Project $project, string $name, int $months): TrainingPlan
    {
        $start = now()->startOfMonth();
        $end = $start->copy()->addMonths($months)->subDay();
        return TrainingPlan::firstOrCreate(
            ['project_id' => $project->id, 'name_ar' => $name],
            ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString(), 'status' => 'active', 'description' => "دورة مدتها {$months} أشهر"]
        );
    }

    private function cohort(Project $project, Employee $manager, string $name, string $shift, string $code): Cohort
    {
        return Cohort::firstOrCreate(
            ['project_id' => $project->id, 'code' => $code],
            ['name' => $name, 'manager_id' => $manager->id, 'shift' => $shift, 'is_active' => true]
        );
    }

    private function subjectCourseAttach(Course $course, array $subjects, array $instructors): void
    {
        // في المعهد المهني بدون مستويات منفصلة، نضيف المادة للمقرر فقط
        foreach ($subjects as $subject) {
            $subject->course_id = $course->id;
            $subject->save();
        }
    }

    /**
     * يملأ خطة بدروس أسبوعية متكررة (أسبوعين نموذجياً) مع حجز وقت لكل مدرّس
     * بحيث لا يعطي أي مدرّس أكثر من صف في نفس اليوم (منع التضارب).
     */
    private function fillWeeklyLessons(TrainingPlan $plan, array $levels, array $subjects): void
    {
        $daySlots = [
            1 => ['10:00', '11:00'],
            2 => ['10:00', '11:00'],
            3 => ['10:00', '11:00'],
            4 => ['12:00', '13:00'],
            5 => ['12:00', '13:00'],
            6 => ['14:00', '15:00'],
        ];
        $instructorBusy = []; // instructor_id => day => true

        foreach ($levels as $lv) {
            foreach ($subjects as $i => $subject) {
                $instructor = $lv->subjectInstructors()
                    ->where('subject_id', $subject->id)
                    ->orderBy('is_main', 'desc')
                    ->first()?->instructor;
                if (!$instructor) { continue; }

                $assigned = false;
                foreach ($daySlots as $day => [$st, $et]) {
                    if (!isset($instructorBusy[$instructor->id][$day])) {
                        $instructorBusy[$instructor->id][$day] = true;
                        for ($week = 1; $week <= 2; $week++) {
                            TrainingPlanLesson::firstOrCreate([
                                'training_plan_id' => $plan->id,
                                'academic_level_id' => $lv->id,
                                'subject_id' => $subject->id,
                                'week_number' => $week,
                                'day_of_week' => $day,
                            ], [
                                'instructor_id' => $instructor->id,
                                'start_time' => $st,
                                'end_time' => $et,
                                'lesson_name' => "درس {$subject->name_ar} - أسبوع {$week}",
                                'location' => 'قاعة ' . ($i + 1),
                            ]);
                        }
                        $assigned = true;
                        break;
                    }
                }
                if (!$assigned) {
                    fwrite(STDOUT, "WARN: no free slot for {$subject->name_ar} in {$lv->name_ar}\n");
                }
            }
        }
    }
}
