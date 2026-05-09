<?php

namespace Database\Seeders;

use App\Models\AuthProvider;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            SystemSettingsSeeder::class,
            RolesPermissionsSeeder::class,
            RhRoleMappingSeeder::class,
            ComercialCatalogosSeeder::class,
            TestUsersSeeder::class,
            ProyectosTestSeeder::class,
            FinanzasSeeder::class,
        ]);

        $admin = User::firstOrCreate(
            ['email' => 'admin@gptservices.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'departamento' => 'Sistemas',
                'puesto' => 'Super Admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        );

        $admin->syncRoles(['super_admin']);

        AuthProvider::firstOrCreate(
            ['provider' => 'email_password', 'provider_user_id' => (string) $admin->id],
            [
                'user_id' => $admin->id,
                'password_hash' => $admin->password,
                'is_primary' => true,
                'linked_at' => now(),
            ],
        );
    }
}
