<?php

namespace Database\Factories;

use App\Models\Admin\Center;
use App\Models\Admin\Project;
use App\Models\Admin\Student\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        $center = Center::inRandomOrder()->first();
        $project = Project::inRandomOrder()->first();

        return [
            'student_code' => 'STU' . $this->faker->unique()->numerify('#####'),
            'first_name_ar' => $this->faker->firstName(),
            'last_name_ar' => $this->faker->lastName(),
            'first_name_en' => $this->faker->firstName(),
            'last_name_en' => $this->faker->lastName(),
            'father_name' => $this->faker->firstName('male'),
            'mother_name' => $this->faker->firstName('female'),
            'birth_date' => $this->faker->date('Y-m-d', '2008-12-31'),
            'birth_place' => $this->faker->city(),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'nationality' => 'سوري',
            'phone' => $this->faker->numerify('09########'),
            'email' => $this->faker->unique()->safeEmail(),
            'address' => $this->faker->address(),
            'center_id' => $center?->id,
            'project_id' => $project?->id,
            'status' => 'active',
            'enrollment_date' => $this->faker->date('Y-m-d', 'now'),
            'notes' => null,
        ];
    }
}
