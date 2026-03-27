<?php

namespace Database\Factories;

use App\Models\Guardian;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GuardianFactory extends Factory
{
    protected $model = Guardian::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        // Create a user with role_id = 4 (Guardian)
        $user = User::factory()->create(['role_id' => 4]);

        return [
            'user_id' => $user->id,
            'occupation' => $this->faker->jobTitle(),
            'relationship' => $this->faker->randomElement(['Mother', 'Father', 'Guardian']),
            'receive_notifications' => true,
        ];
    }

    /**
     * Indicate that the guardian should not receive notifications.
     */
    public function withoutNotifications(): static
    {
        return $this->state(fn (array $attributes) => [
            'receive_notifications' => false,
        ]);
    }

    /**
     * Attach an existing user to the guardian.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
