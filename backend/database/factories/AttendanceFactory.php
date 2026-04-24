<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Period;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'grade_id' =>Grade::factory(),
            'student_id' => Student::factory(),
            'teacher_id' => Teacher::factory(),
            'subject_id' => Subject::factory(),
            'section_id' => Section::factory(),
            'period_id' => Period::factory(),
            'date' => $this->faker->date(),  /** date is stored as a date string (y-m-d), which mathes the column type */
            'status' => $this->faker->randomElement(['present', 'absent', 'late', 'permission']),
            'remarks' => $this->faker->optional()->sentence(),
        ];
    }
    /**
     * Summary of present
     * @return AttendanceFactory
     * === convenience states for common statuses
     */
    public function present(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'present',
        ]);
    }

    public function absent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'absent',
        ]);
    }

    public function late(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'late',
        ]);
    }

    public function permission(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'permission',
        ]);
    }

    /**
     *
     * convience methods for seeting specific relationships
     * @param Student $student
     * @return AttendanceFactory
     */
    public function forStudent(Student $student): static
    {
        return $this->state(fn (array $attributes) => [
            'student_id' => $student->id,
        ]);
    }

    public function forTeacher(Teacher $teacher): static
    {
        return $this->state(fn (array $attributes) => [
            'teacher_id' => $teacher->id,
        ]);
    }

    public function forSubject(Subject $subject): static
    {
        return $this->state(fn (array $attributes) => [
            'subject_id' => $subject->id,
        ]);
    }

    public function forSection(Section $section): static
    {
        return $this->state(fn (array $attributes) => [
            'section_id' => $section->id,
        ]);
    }

    public function forPeriod(Period $period): static
    {
        return $this->state(fn (array $attributes) => [
            'period_id' => $period->id,
        ]);
    }

    /**
     * set a specific date for the attendance record
     * @param string $date
     * @return AttendanceFactory
     */
     // the dtae is stored as Y-MD , maching the database solumn type
    public function onDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $date,
        ]);
    }
}
