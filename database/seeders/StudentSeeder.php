<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Student;
use App\Models\Guardian;
use App\Models\Grade;
use App\Models\Section;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Get all guardians
        $guardians = Guardian::all();
        if ($guardians->isEmpty()) {
            $this->command->error('Please run GuardianSeeder first!');
            return;
        }

        // Get all grades and sections
        $grades = Grade::all();
        $sections = Section::all()->groupBy('grade_id');

        $students = [
            [
                'name' => 'Dawit Abate',
                'email' => 'dawit.a@student.com',
                'addmission' => 'STU2024002',
                'grade' => 'Grade 9',
                'section' => 'A',
                'dob' => '2009-03-20',
                'gender' => 'male',
            ],
            [
                'name' => 'Hiwot Kindu',
                'email' => 'hiwot.k@student.com',
                'addmission' => 'STU2024003',
                'grade' => 'Grade 9',
                'section' => 'B',
                'dob' => '2008-08-10',
                'gender' => 'female',
            ],
            [
                'name' => 'Meron Abebe',
                'email' => 'meron.a@student.com',
                'addmission' => 'STU2024004',
                'grade' => 'Grade 10',
                'section' => 'A',
                'dob' => '2007-06-30',
                'gender' => 'female',
            ],
            [
                'name' => 'Yared Adane',
                'email' => 'yared.a@student.com',
                'addmission' => 'STU2024005',
                'grade' => 'Grade 10',
                'section' => 'B',
                'dob' => '2007-04-05',
                'gender' => 'male',
            ],
            [
                'name' => 'Eyob Demeke',
                'email' => 'eyob.d@student.com',
                'addmission' => 'STU2024006',
                'grade' => 'Grade 11',
                'section' => 'A',
                'dob' => '2006-09-12',
                'gender' => 'male',
            ],
            [
                'name' => 'Kidist Yihune',
                'email' => 'kidist.y@student.com',
                'addmission' => 'STU2024007',
                'grade' => 'Grade 11',
                'section' => 'C',
                'dob' => '2006-02-28',
                'gender' => 'female',
            ],
            [
                'name' => 'Fasil Tesfa',
                'email' => 'fasil.t@student.com',
                'addmission' => 'STU2024008',
                'grade' => 'Grade 12',
                'section' => 'A',
                'dob' => '2005-12-18',
                'gender' => 'male',
            ],
        ];

        foreach ($students as $studentData) {
            // Find grade and section
            $grade = $grades->firstWhere('name', $studentData['grade']);
            if (!$grade) continue;

            $section = $sections[$grade->id]->firstWhere('name', $studentData['section']);
            if (!$section) continue;

            // Assign a random guardian
            $guardian = $guardians->random();

            // Create user account
            $user = User::firstOrCreate(
                ['email' => $studentData['email']],
                [
                    'name' => $studentData['name'],
                    'password' => Hash::make('password'),
                    'phone' => $faker->phoneNumber,
                    'role_id' => 3,
                    'is_active' => true,
                ]
            );

            // Create student profile
            Student::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'guardian_id' => $guardian->id,
                    'grade_id' => $grade->id,
                    'section_id' => $section->id,
                    'addmission_number' => $studentData['addmission'],
                    'date_of_birth' => $studentData['dob'],
                    'gender' => $studentData['gender'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('✅ Students seeded successfully!');
    }
}
