<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // clear existing records
        DB::table('roles')->truncate();

        $roles = [
            ['name' => 'Admin', 'description' => 'System administrator with full access', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Teacher', 'description' => 'Can mark attendance and view class reports', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Student', 'description' => 'Can view own attendance records', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Guardian', 'description' => 'Can view child attendance and receive notifications', 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }

        $this->command->info('Roles seeded successfully!');
    }
}
