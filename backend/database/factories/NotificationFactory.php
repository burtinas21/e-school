<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\Student;
use App\Models\Guardian;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id'  => null,
            'guardian_id' => null,
            'teacher_id'  => null,
            'type'        => $this->faker->randomElement(['absence', 'late', 'permission', 'daily_summary', 'warning', 'event']),
            'title'       => $this->faker->sentence(),
            'message'     => $this->faker->paragraph(),
            'sent_at'     => now(),
            'read_at'     => null,
            'status'      => 'sent',
        ];
    }

    /**
     * Set a specific teacher for the notification.
     */
    public function forTeacher(\App\Models\Teacher $teacher): static
    {
        return $this->state(fn (array $attributes) => [
            'teacher_id' => $teacher->id,
        ]);
    }

    /**
     * Set the notification as unread.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    /**
     * Set the notification as read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }

    /**
     * Set a specific student for the notification.
     */
    public function forStudent(Student $student): static
    {
        return $this->state(fn (array $attributes) => [
            'student_id' => $student->id,
        ]);
    }

    /**
     * Set a specific guardian for the notification.
     */
    public function forGuardian(Guardian $guardian): static
    {
        return $this->state(fn (array $attributes) => [
            'guardian_id' => $guardian->id,
        ]);
    }

    /**
     * Set a specific notification type.
     */
    public function ofType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }

    /**
     * Mark notification as sent (default).
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
        ]);
    }

    /**
     * Mark notification as pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Mark notification as failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }
}
