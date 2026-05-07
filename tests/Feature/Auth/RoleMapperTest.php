<?php

use App\Models\User;
use App\Services\Auth\RoleMapper;
use Database\Seeders\RhRoleMappingSeeder;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RolesPermissionsSeeder::class, RhRoleMappingSeeder::class]);
    RoleMapper::flushCache();
});

it('asigna gerente_proyectos al puesto Gerente de Proyectos', function () {
    $user = User::factory()->create(['puesto' => 'Gerente de Proyectos']);

    expect(app(RoleMapper::class)->resolveRoleForUser($user))->toBe('gerente_proyectos');
});

it('asigna direccion_general a Director General', function () {
    $user = User::factory()->create(['puesto' => 'Director General']);

    expect(app(RoleMapper::class)->resolveRoleForUser($user))->toBe('direccion_general');
});

it('cae al fallback ingeniero_proyectos cuando no hay match', function () {
    $user = User::factory()->create(['puesto' => 'Puesto Inventado XYZ']);

    expect(app(RoleMapper::class)->resolveRoleForUser($user))->toBe('ingeniero_proyectos');
});

it('respeta prioridad: Director DN gana frente a Director (genérico)', function () {
    $user = User::factory()->create(['puesto' => 'Director de Desarrollo de Negocios']);

    expect(app(RoleMapper::class)->resolveRoleForUser($user))->toBe('director_dn');
});
