<?php

use App\Models\AuthProvider;
use App\Models\EmailAllowlist;
use App\Models\SocioAllowlist;
use App\Models\User;
use App\Services\Auth\SocioResolver;
use Database\Seeders\ComercialCatalogosSeeder;
use Database\Seeders\RhRoleMappingSeeder;
use Database\Seeders\RolesPermissionsSeeder;
use Database\Seeders\SystemSettingsSeeder;
use Database\Seeders\TestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        SystemSettingsSeeder::class,
        RolesPermissionsSeeder::class,
        RhRoleMappingSeeder::class,
        ComercialCatalogosSeeder::class,
        TestUsersSeeder::class,
    ]);
});

it('crea 26 usuarios cubriendo 21 de los 22 roles (super_admin lo siembra DatabaseSeeder)', function () {
    expect(User::count())->toBe(26);

    foreach ([
        'direccion_general', 'socio', 'comite_socios', 'director_dn',
        'comercial', 'gerente_proyectos', 'ingeniero_costos', 'ingeniero_proyectos',
        'trainee_proyectos', 'gerente_operaciones', 'serv_tecnicos', 'soldadura',
        'serv_generales', 'qhse', 'almacen', 'manufactura', 'compras',
        'ingenieria_diseño', 'cfo', 'analista_financiero', 'finanzas_general',
        'auditor_externo', 'cliente_externo',
    ] as $role) {
        expect(User::role($role)->exists())->toBeTrue("Falta usuario con rol {$role}");
    }
});

it('todos los usuarios tienen password_hash que valida con "password"', function () {
    $users = User::whereHas('authProviders', fn ($q) => $q->where('provider', 'email_password'))->get();

    expect($users)->not->toBeEmpty();

    foreach ($users as $user) {
        $provider = $user->authProviders()->where('provider', 'email_password')->first();
        expect(Hash::check('password', $provider->password_hash))
            ->toBeTrue("Password no valida para {$user->email}");
    }
});

it('Guillermo (DG) es socio automáticamente vía RH mock', function () {
    $user = User::where('email', 'gguterrez@gptservices.com')->first();

    expect(app(SocioResolver::class)->isSocio($user))->toBeTrue();
});

it('Carla con override = true es socia aunque su puesto no sea DG', function () {
    $user = User::where('email', 'cmendez@gptservices.com')->first();

    expect($user->es_socio_override)->toBeTrue()
        ->and(app(SocioResolver::class)->isSocio($user))->toBeTrue();
});

it('Inversionista externo es socio vía allowlist (sin RH ni override)', function () {
    $user = User::where('email', 'inversor@familyoffice.example')->first();

    expect($user->es_socio_override)->toBeNull()
        ->and(SocioAllowlist::where('email', 'inversor@familyoffice.example')->exists())->toBeTrue()
        ->and(app(SocioResolver::class)->isSocio($user))->toBeTrue();
});

it('externos quedan en email_allowlist con su provider', function () {
    expect(EmailAllowlist::where('email', 'auditor@kpmg.example')->first()->allowed_providers)
        ->toBe(['google'])
        ->and(EmailAllowlist::where('email', 'pm@igasamex.example')->first()->allowed_providers)
        ->toBe(['microsoft']);
});

it('usuario suspendido tiene status correcto', function () {
    $user = User::where('email', 'suspendido@gptservices.com')->first();

    expect($user->status)->toBe('suspended');
});

it('cada usuario tiene un AuthProvider email_password único', function () {
    $userIds = User::pluck('id');

    foreach ($userIds as $id) {
        expect(AuthProvider::where('user_id', $id)->where('provider', 'email_password')->count())
            ->toBe(1, "Usuario {$id} no tiene exactamente un email_password provider");
    }
});

it('es idempotente — correr el seeder dos veces no duplica usuarios', function () {
    $countBefore = User::count();

    $this->seed(TestUsersSeeder::class);

    expect(User::count())->toBe($countBefore);
});
