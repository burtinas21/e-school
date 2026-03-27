<?php

namespace Database\Factories;

use App\Models\TeacherAssignment;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\Section;
use App\Models\Grade;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeacherAssignmentFactory extends Factory
{
    protected $model = TeacherAssignment::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        // Create a grade to ensure subject and section belong to the same grade
        $grade = Grade::factory()->create();

        $subject = Subject::factory()->create(['grade_id' => $grade->id]);
        $section = Section::factory()->create(['grade_id' => $grade->id]);
        $teacher = Teacher::factory()->create();

        return [
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'academic_year' => now()->year,
            'is_primary' => $this->faker->boolean(80),
        ];
    }

    /**
     * Assign a specific teacher.
     */
    public function forTeacher(Teacher $teacher): static
    {
        return $this->state(fn (array $attributes) => [
            'teacher_id' => $teacher->id,
        ]);
    }

    /**
     * Assign a specific subject.
     * If a section is not already set, it will be created with the same grade as the subject.
     */
    public function forSubject(Subject $subject): static
    {
        return $this->state(function (array $attributes) use ($subject) {
            if (!isset($attributes['section_id'])) {
                $section = Section::factory()->create(['grade_id' => $subject->grade_id]);
                return [
                    'subject_id' => $subject->id,
                    'section_id' => $section->id,
                ];
            }
            return ['subject_id' => $subject->id];
        });
    }

    /**
     * Assign a specific section.
     * If a subject is not already set, it will be created with the same grade as the section.
     */
    public function forSection(Section $section): static
    {
        return $this->state(function (array $attributes) use ($section) {
            if (!isset($attributes['subject_id'])) {
                $subject = Subject::factory()->create(['grade_id' => $section->grade_id]);
                return [
                    'section_id' => $section->id,
                    'subject_id' => $subject->id,
                ];
            }
            return ['section_id' => $section->id];
        });
    }

    /**
     * Set a specific academic year.
     */
    public function forYear(int $year): static
    {
        return $this->state(fn (array $attributes) => [
            'academic_year' => $year,
        ]);
    }

    /**
     * Mark assignment as primary.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }

    /**
     * Mark assignment as secondary.
     */
    public function secondary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => false,
        ]);
    }
}
