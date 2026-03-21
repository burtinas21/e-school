<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Section;
use App\Models\Grade;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Section>
 */
class SectionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Section::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Available section names
        $sections = ['A', 'B', 'C', 'D'];

        return [
            // This will automatically create a Grade and use its ID
            'grade_id' => Grade::factory(),
            'name' => $this->faker->randomElement($sections),
            'is_active' => $this->faker->boolean(95), // 95% chance active
        ];
    }

    /**
     * Indicate that the section is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set a specific section name.
     */
    public function name(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }

    /**
     * Attach to a specific grade.
     */
    public function forGrade(Grade $grade): static
    {
        return $this->state(fn (array $attributes) => [
            'grade_id' => $grade->id,
        ]);
    }
}
