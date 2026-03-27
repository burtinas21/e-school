<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Subject;
use App\Models\Grade;

class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        $subjects = ['Mathematics', 'English', 'Physics', 'Chemistry', 'Biology', 'History', 'Geography', 'ICT'];
        $name = $this->faker->randomElement($subjects);

        return [
            'name' => $name,
            'grade_id' => Grade::factory(),
            'subject_code' => strtoupper(substr($name, 0, 3)) . $this->faker->unique()->numberBetween(100, 999),
            'description' => $this->faker->sentence,
            'credits' => $this->faker->randomElement([2.0, 3.0, 4.0]),
            'is_core' => $this->faker->boolean(80),
            'is_active' => true,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
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

    public function withCode(string $code): static
    {
        return $this->state(fn (array $attributes) => [
            'subject_code' => $code,
        ]);
    }
}
