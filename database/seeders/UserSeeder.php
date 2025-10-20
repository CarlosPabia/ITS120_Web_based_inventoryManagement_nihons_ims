<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $managerRole = Role::where('role_name', 'Manager')->first();
        $employeeRole = Role::where('role_name', 'Employee')->first();

        if (!$managerRole || !$employeeRole) {
            $this->command?->warn('Roles not found. Run RoleSeeder first.');
            return;
        }

        User::updateOrCreate(
            ['email' => 'admin@your-domain.com'],
            [
                'employee_id'   => 'ADM-0001',
                'password_hash' => Hash::make('Passw0rd!'),
                'first_name'    => 'Admin',
                'last_name'     => 'User',
                'starting_date' => now()->toDateString(),
                'role_id'       => $managerRole->id,
                'is_active'     => true,
                'remember_token'=> Str::random(10),
            ]
        );

        User::updateOrCreate(
            ['email' => 'staff@your-domain.com'],
            [
                'employee_id'   => 'EMP-0001',
                'password_hash' => Hash::make('Passw0rd!'),
                'first_name'    => 'Staff',
                'last_name'     => 'Member',
                'starting_date' => now()->toDateString(),
                'role_id'       => $employeeRole->id,
                'is_active'     => true,
                'remember_token'=> Str::random(10),
            ]
        );
    }
}

