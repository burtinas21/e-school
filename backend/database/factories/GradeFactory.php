<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Grade;

class GradeFactory extends Factory
{
    protected $model = Grade::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $levels = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
        $level = $this->faker->randomElement($levels);

        return [
            'name' => 'Grade ' . $level,
            'level' => $level,
            'is_active' => $this->faker->boolean(90),
        ];
    }

    /**
     * Indicate that the grade is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the grade is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set a specific grade level.
     */
    public function level(int $level): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Grade ' . $level,
            'level' => $level,
        ]);
    }
}
