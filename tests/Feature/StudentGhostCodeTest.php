<?php

use App\Models\Admin\Student\Student;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['type' => 'super-admin']);
    $this->actingAs($this->admin);
});

test('store blocks a student_code that belongs to a soft-deleted student with a clear message', function () {
    $ghost = Student::create([
        'student_code' => 'STU-GHOST-1',
        'first_name_ar' => 'نازلية',
        'last_name_ar' => 'حسين',
        'gender' => 'female',
        'status' => 'active',
    ]);
    $ghost->delete();

    $response = $this->post(route('admin.students.store'), [
        'student_code' => 'STU-GHOST-1',
        'first_name_ar' => 'شخص',
        'last_name_ar' => 'آخر',
        'gender' => 'female',
        'status' => 'active',
    ]);

    $response->assertSessionHasErrors('student_code');
    $errors = session('errors')->get('student_code');
    expect(implode(' ', $errors))->toContain('محذوف');
});

test('store succeeds for a fresh unused code', function () {
    $response = $this->post(route('admin.students.store'), [
        'student_code' => 'STU-FRESH-1',
        'first_name_ar' => 'طالب',
        'last_name_ar' => 'جديد',
        'gender' => 'male',
        'status' => 'active',
    ]);

    $response->assertSessionHasNoErrors();
    expect(Student::where('student_code', 'STU-FRESH-1')->exists())->toBeTrue();
});
