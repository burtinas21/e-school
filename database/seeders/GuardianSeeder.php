<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Guardian;
use Illuminate\Support\Facades\Hash;

class GuardianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guardians = [
            [
                'name' => 'Tigist Haile',
                'email' => 'tigist.h@parent.com',
                'phone' => '0912000001',
                'occupation' => 'Nurse',
                'relationship' => 'Mother',
            ],
            [
                'name' => 'Girma Tadesse',
                'email' => 'girma.t@parent.com',
                'phone' => '0912000002',
                'occupation' => 'Engineer',
                'relationship' => 'Father',
            ],
            [
                'name' => 'Hana Kebede',
                'email' => 'hana.k@parent.com',
                'phone' => '0912000003',
                'occupation' => 'Doctor',
                'relationship' => 'Mother',
            ],
            [
                'name' => 'Solomon Ayele',
                'email' => 'solomon.a@parent.com',
                'phone' => '0912000004',
                'occupation' => 'Businessman',
                'relationship' => 'Father',
            ],
        ];

        foreach ($guardians as $guardianData) {
            // Create user account
            $user = User::firstOrCreate(
                ['email' => $guardianData['email']],
                [
                    'name' => $guardianData['name'],
                    'password' => Hash::make('password'),
                    'phone' => $guardianData['phone'],
                    'role_id' => 4,
                    'is_active' => true,
                ]
            );

            // Create guardian profile
            Guardian::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'occupation' => $guardianData['occupation'],
                    'relationship' => $guardianData['relationship'],
                    'receive_notifications' => true,
                ]
            );
        }

        $this->command->info('✅ Guardians seeded successfully!');
    }
}
