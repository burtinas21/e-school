<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subject;
use App\Models\Grade;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $grades = Grade::all();

        $subjectsByGrade = [
            'Grade 9' => [
                ['name' => 'Mathematics', 'code' => 'MATH101', 'credits' => 4, 'is_core' => true],
                ['name' => 'English', 'code' => 'ENG101', 'credits' => 3, 'is_core' => true],
                ['name' => 'Physics', 'code' => 'PHY101', 'credits' => 3, 'is_core' => true],
                ['name' => 'Chemistry', 'code' => 'CHEM101', 'credits' => 3, 'is_core' => true],
                ['name' => 'Biology', 'code' => 'BIO101', 'credits' => 3, 'is_core' => true],
                ['name' => 'History', 'code' => 'HIST101', 'credits' => 2, 'is_core' => false],
            ],
            'Grade 10' => [
                ['name' => 'Mathematics', 'code' => 'MATH201', 'credits' => 4, 'is_core' => true],
                ['name' => 'English', 'code' => 'ENG201', 'credits' => 3, 'is_core' => true],
                ['name' => 'Physics', 'code' => 'PHY201', 'credits' => 3, 'is_core' => true],
                ['name' => 'Chemistry', 'code' => 'CHEM201', 'credits' => 3, 'is_core' => true],
                ['name' => 'Biology', 'code' => 'BIO201', 'credits' => 3, 'is_core' => true],
                ['name' => 'Geography', 'code' => 'GEO201', 'credits' => 2, 'is_core' => false],
            ],
            'Grade 11' => [
                ['name' => 'Mathematics', 'code' => 'MATH301', 'credits' => 4, 'is_core' => true],
                ['name' => 'English', 'code' => 'ENG301', 'credits' => 3, 'is_core' => true],
                ['name' => 'Physics', 'code' => 'PHY301', 'credits' => 3, 'is_core' => true],
                ['name' => 'Chemistry', 'code' => 'CHEM301', 'credits' => 3, 'is_core' => true],
                ['name' => 'Biology', 'code' => 'BIO301', 'credits' => 3, 'is_core' => true],
                ['name' => 'ICT', 'code' => 'ICT301', 'credits' => 2, 'is_core' => false],
            ],
            'Grade 12' => [
                ['name' => 'Mathematics', 'code' => 'MATH401', 'credits' => 4, 'is_core' => true],
                ['name' => 'English', 'code' => 'ENG401', 'credits' => 3, 'is_core' => true],
                ['name' => 'Physics', 'code' => 'PHY401', 'credits' => 3, 'is_core' => true],
                ['name' => 'Chemistry', 'code' => 'CHEM401', 'credits' => 3, 'is_core' => true],
                ['name' => 'Biology', 'code' => 'BIO401', 'credits' => 3, 'is_core' => true],
                ['name' => 'Economics', 'code' => 'ECON401', 'credits' => 2, 'is_core' => false],
            ],
        ];

        foreach ($grades as $grade) {
            if (isset($subjectsByGrade[$grade->name])) {
                foreach ($subjectsByGrade[$grade->name] as $subjectData) {
                    Subject::firstOrCreate(
                        [
                            'grade_id' => $grade->id,
                            'name' => $subjectData['name'],
                        ],
                        [
                            'subject_code' => $subjectData['code'],
                            'credits' => $subjectData['credits'],
                            'is_core' => $subjectData['is_core'],
                            'description' => $subjectData['name'] . ' for ' . $grade->name,
                            'is_active' => true,
                        ]
                    );
                }
            }
        }

        $this->command->info('✅ Subjects seeded successfully!');
    }
}
