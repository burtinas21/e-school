<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TeacherAssignment;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\Section;

class TeacherAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $academicYear = now()->year;

        // Get all teachers
        $teachers = Teacher::with('user')->get();

        // Get all subjects
        $subjects = Subject::all();

        // Get all sections
        $sections = Section::with('grade')->get();

        $assignments = [];

        // Create assignments for each teacher
        foreach ($teachers as $teacher) {
            // For each teacher, assign 2-3 subjects
            $teacherSubjects = $subjects->random(min(3, $subjects->count()));

            foreach ($teacherSubjects as $subject) {
                // Find sections that match the subject's grade
                $matchingSections = $sections->filter(function($section) use ($subject) {
                    return $section->grade_id == $subject->grade_id;
                });

                if ($matchingSections->count() > 0) {
                    // Assign to 1-2 sections for this subject
                    $sectionCount = min(2, $matchingSections->count());
                    $assignedSections = $matchingSections->random($sectionCount);

                    foreach ($assignedSections as $section) {
                        $assignments[] = [
                            'teacher_id' => $teacher->id,
                            'subject_id' => $subject->id,
                            'section_id' => $section->id,
                            'academic_year' => $academicYear,
                            'is_primary' => true,
                        ];
                    }
                }
            }
        }

        // Add specific known assignments for Mr. Fasil
        $fasil = Teacher::whereHas('user', function($q) {
            $q->where('email', 'fasil@school.com');
        })->first();

        if ($fasil) {
            $math9 = Subject::where('name', 'Mathematics')->whereHas('grade', function($q) {
                $q->where('name', 'Grade 9');
            })->first();

            $math10 = Subject::where('name', 'Mathematics')->whereHas('grade', function($q) {
                $q->where('name', 'Grade 10');
            })->first();

            $grade9Sections = Section::whereHas('grade', function($q) {
                $q->where('name', 'Grade 9');
            })->get();

            $grade10Sections = Section::whereHas('grade', function($q) {
                $q->where('name', 'Grade 10');
            })->get();

            if ($math9 && $grade9Sections->count() > 0) {
                foreach ($grade9Sections as $section) {
                    TeacherAssignment::firstOrCreate(
                        [
                            'teacher_id' => $fasil->id,
                            'subject_id' => $math9->id,
                            'section_id' => $section->id,
                            'academic_year' => $academicYear,
                        ],
                        [
                            'is_primary' => true,
                        ]
                    );
                }
            }

            if ($math10 && $grade10Sections->count() > 0) {
                foreach ($grade10Sections as $section) {
                    TeacherAssignment::firstOrCreate(
                        [
                            'teacher_id' => $fasil->id,
                            'subject_id' => $math10->id,
                            'section_id' => $section->id,
                            'academic_year' => $academicYear,
                        ],
                        [
                            'is_primary' => true,
                        ]
                    );
                }
            }
        }

        // Create all assignments
        foreach ($assignments as $assignment) {
            TeacherAssignment::firstOrCreate(
                [
                    'teacher_id' => $assignment['teacher_id'],
                    'subject_id' => $assignment['subject_id'],
                    'section_id' => $assignment['section_id'],
                    'academic_year' => $assignment['academic_year'],
                ],
                [
                    'is_primary' => $assignment['is_primary'],
                ]
            );
        }

        $count = TeacherAssignment::count();
        $this->command->info("✅ $count teacher assignments seeded successfully!");
    }
}
