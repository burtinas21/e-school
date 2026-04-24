<?php

namespace Database\Factories;

use App\Models\Period;
use Illuminate\Database\Eloquent\Factories\Factory;

class PeriodFactory extends Factory
{
    protected $model = Period::class;

    public function definition(): array
    {
        static $number = 0;
        $number++;
        return [
            'name' => 'Period ' . $number,
            'period_number' => $number,
            'start_time' => $this->faker->time(),
            'end_time' => $this->faker->time(),
            'is_break' => false,
            'break_name' => null,
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

    public function class(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_break' => false,
            'break_name' => null,
        ]);
    }

    public function break(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_break' => true,
            'break_name' => $this->faker->word(),
        ]);
    }

    public function withNumber(int $number): static
    {
        return $this->state(fn (array $attributes) => [
            'period_number' => $number,
            'name' => 'Period ' . $number,
        ]);
    }
}
