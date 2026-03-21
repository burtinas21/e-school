<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Teacher;
use Illuminate\Support\Facades\Hash;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teachers = [
            [
                'name' => 'Ms. Selam',
                'email' => 'selam@school.com',
                'employee_id' => 'TCH002',
                'qualification' => 'M.Ed in English',
                'phone' => '0911000004',
                'hire_date' => '2019-09-01',
            ],
            [
                'name' => 'Mr. Tewodros',
                'email' => 'tewodros@school.com',
                'employee_id' => 'TCH003',
                'qualification' => 'Ph.D in Physics',
                'phone' => '0911000005',
                'hire_date' => '2018-09-01',
            ],
            [
                'name' => 'Mrs. Amira',
                'email' => 'amira@school.com',
                'employee_id' => 'TCH004',
                'qualification' => 'M.Sc in Chemistry',
                'phone' => '0911000006',
                'hire_date' => '2020-09-01',
            ],
            [
                'name' => 'Mr. Yonas',
                'email' => 'yonas@school.com',
                'employee_id' => 'TCH005',
                'qualification' => 'B.Ed in Biology',
                'phone' => '0911000007',
                'hire_date' => '2021-09-01',
            ],
            [
                'name' => 'Ms. Genet',
                'email' => 'genet@school.com',
                'employee_id' => 'TCH006',
                'qualification' => 'M.Ed in History',
                'phone' => '0911000008',
                'hire_date' => '2022-09-01',
            ],
        ];

        foreach ($teachers as $teacherData) {
            // Create user account
            $user = User::firstOrCreate(
                ['email' => $teacherData['email']],
                [
                    'name' => $teacherData['name'],
                    'password' => Hash::make('password'),
                    'phone' => $teacherData['phone'],
                    'role_id' => 2,
                    'is_active' => true,
                ]
            );

            // Create teacher profile
            Teacher::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'employee_id' => $teacherData['employee_id'],
                    'qualification' => $teacherData['qualification'],
                    'hire_date' => $teacherData['hire_date'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('✅ Teachers seeded successfully!');
    }
}
