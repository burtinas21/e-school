<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Guardian;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@school.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'phone' => '0911000000',
                'role_id' => 1,
                'is_active' => true,
            ]
        );

        // Teacher
        $teacherUser = User::firstOrCreate(
            ['email' => 'fasil@school.com'],
            [
                'name' => 'Mr. Fasil',
                'password' => Hash::make('password'),
                'phone' => '0911000001',
                'role_id' => 2,
                'is_active' => true,
            ]
        );

        Teacher::firstOrCreate(
            ['user_id' => $teacherUser->id],
            [
                'employee_id' => 'TCH001',
                'qualification' => 'M.Ed in Mathematics',
                'hire_date' => '2020-01-01',
                'is_active' => true,
            ]
        );

        // Parent
        $parentUser = User::firstOrCreate(
            ['email' => 'parent@email.com'],
            [
                'name' => 'Abebech Desta',
                'password' => Hash::make('password'),
                'phone' => '0911000002',
                'role_id' => 4,
                'is_active' => true,
            ]
        );

        $parent = Guardian::firstOrCreate(
            ['user_id' => $parentUser->id],
            [
                'occupation' => 'Teacher',
                'relationship' => 'Mother',
                'receive_notifications' => true,
            ]
        );

        // Student
        $studentUser = User::firstOrCreate(
            ['email' => 'bereket@student.com'],
            [
                'name' => 'Bereket Melkamu',
                'password' => Hash::make('password'),
                'phone' => '0911000003',
                'role_id' => 3,
                'is_active' => true,
            ]
        );

        Student::firstOrCreate(
            ['user_id' => $studentUser->id],
            [
                'guardian_id' => $parent->id,
                'grade_id' => 1,
                'section_id' => 1,
                'addmission_number' => 'STU2024001',
                'date_of_birth' => '2008-05-15',
                'gender' => 'male',
                'is_active' => true,
            ]
        );

        $this->command->info('Users seeded successfully!');
    }
}
