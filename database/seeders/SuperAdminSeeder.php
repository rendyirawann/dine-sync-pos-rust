<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Reset Cached Roles/Permissions (Wajib agar tidak error cache)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Buat Role 'Superadmin' jika belum ada
        $role = Role::firstOrCreate(
            ['name' => 'Superadmin', 'guard_name' => 'web']
        );

        // 3. Buat User Superadmin
        // Di dalam file SuperAdminSeeder.php bagian create user
        $user = User::firstOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'name'              => 'Super Administrator',
                'username'          => 'superadmin', // Pastikan username diisi
                'password'          => Hash::make('12qwaszx123!!@@##'),
                'no_wa'             => '08123456789',
                'email_verified_at' => now(),
                'is_active'         => true,
            ]
        );

        // 4. Assign Role Superadmin ke User tersebut
        if (!$user->hasRole('Superadmin')) {
            $user->assignRole($role);
        }

        $this->command->info('User Superadmin berhasil dibuat!');
        $this->command->info('Email: superadmin@gmail.com');
        $this->command->info('Pass : 12qwaszx123!!@@##');
    }
}
