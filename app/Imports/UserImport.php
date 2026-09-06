<?php

namespace App\Imports;

use App\Models\Admin\Center;
use App\Models\Admin\Hr\JobPosition;
use App\Models\Admin\Project;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;

class UserImport implements ToCollection
{
    public function collection(Collection $rows): void
    {
        // Rows are read positionally to avoid heading transliteration issues:
        // 0=name, 1=email, 2=official_email, 3=type, 4=job_title, 5=center, 6=project, 7=status
        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue; // skip header row
            }

            $name = trim((string) ($row[0] ?? ''));
            $email = trim((string) ($row[1] ?? ''));
            if ($name === '' || $email === '') {
                continue;
            }

            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'official_email' => $this->clean($row[2] ?? null),
                    'password' => Hash::make('Password@123'),
                    'type' => $this->mapType((string) ($row[3] ?? 'employee')),
                    'job_title_id' => $this->resolveJobTitle($this->clean($row[4] ?? null)),
                    'center_id' => $this->resolveCenter($this->clean($row[5] ?? null)),
                    'project_id' => $this->resolveProject($this->clean($row[6] ?? null)),
                    'is_active' => $this->mapActive((string) ($row[7] ?? 'active')),
                    'must_change_password' => true,
                ]
            );
        }
    }

    private function clean(mixed $value): ?string
    {
        $v = trim((string) $value);
        if ($v === '' || in_array($v, ['—', '-', 'NULL', 'null'], true)) {
            return null;
        }
        return $v;
    }

    private function mapType(string $type): string
    {
        $types = [
            'سوبر أدمن' => 'super-admin',
            'super-admin' => 'super-admin',
            'موظف' => 'employee',
            'employee' => 'employee',
            'مستفيد' => 'beneficiary',
            'beneficiary' => 'beneficiary',
            'طالب' => 'student',
            'student' => 'student',
        ];

        return $types[trim($type)] ?? 'employee';
    }

    private function mapActive(string $status): bool
    {
        return in_array(trim($status), ['active', 'نشط', '1', 1, 'true'], true);
    }

    private function resolveJobTitle(?string $title): ?int
    {
        if ($title === null) {
            return null;
        }
        $job = JobPosition::where('title_ar', $title)->orWhere('title_en', $title)->first();
        if ($job) {
            return $job->id;
        }
        return JobPosition::firstOrCreate(['title_ar' => $title])->id;
    }

    private function resolveCenter(?string $name): ?int
    {
        if ($name === null) {
            return null;
        }
        return Center::where('name', $name)->first()?->id;
    }

    private function resolveProject(?string $name): ?int
    {
        if ($name === null) {
            return null;
        }
        return Project::where('name', $name)->first()?->id;
    }
}