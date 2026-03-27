<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\User;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Guardian;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->create(['role_id' => 3]),
            'guardian_id' => null, // nullable – we will set in specific tests if needed
            'grade_id' => Grade::factory(),
            'section_id' => Section::factory(),
            'addmission_number' => $this->faker->unique()->numerify('STU####'),
            'date_of_birth' => $this->faker->date(),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function forGrade(Grade $grade): static
    {
        return $this->state(fn (array $attributes) => [
            'grade_id' => $grade->id,
        ]);
    }

    public function forSection(Section $section): static
    {
        return $this->state(fn (array $attributes) => [
            'section_id' => $section->id,
        ]);
    }

    public function withGuardian(Guardian $guardian): static
    {
        return $this->state(fn (array $attributes) => [
            'guardian_id' => $guardian->id,
        ]);
    }
}
