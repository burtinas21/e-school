<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Teacher;
use App\Models\User;

class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition(): array
    {
        $user = User::factory()->create(['role_id' => 2]);

        return [
            'user_id' => $user->id,
            'employee_id' => 'TCH' . $this->faker->unique()->numberBetween(100, 999),
            'qualification' => $this->faker->randomElement(['B.Ed', 'M.Ed', 'Ph.D']),
            'hire_date' => $this->faker->dateTimeBetween('-10 years', 'now'),
            'is_active' => true,
        ];
    }

    public function active(): static
    {
       return $this->state(fn (array $attributes) => [
        'is_active' =>true,
       ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
