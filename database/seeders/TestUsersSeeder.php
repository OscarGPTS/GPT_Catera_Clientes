<?php

namespace Database\Seeders;

use App\Models\AuthProvider;
use App\Models\EmailAllowlist;
use App\Models\SocioAllowlist;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Pueblo el sistema con un usuario por cada rol del plan (D13) y casos especiales:
 *   - socio por puesto RH (Guillermo, Director General)
 *   - socio por override manual
 *   - socio por allowlist (externo)
 *   - usuario suspendido
 *   - cliente externo
 *
 * Password de todos: "password".
 *
 * Importante: este seeder usa los emails y employee_id que ya están en RhClientMock,
 * así un test del AuthOrchestrator que haga login Auth0 con esos mismos emails reusa
 * la cuenta en lugar de provisionar una nueva.
 */
class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        $passwordHash = Hash::make('password');

        $users = [
            // — Dirección y socios ——————————————————————————————————
            [
                'name' => 'Guillermo Gutiérrez Melo',
                'email' => 'gguterrez@gptservices.com',
                'employee_id' => '1002',
                'departamento' => 'Dirección',
                'puesto' => 'Director General',
                'role' => 'direccion_general',
                // Será socio automáticamente vía SocioResolver (puesto RH = DG).
            ],
            [
                'name' => 'Carla Méndez',
                'email' => 'cmendez@gptservices.com',
                'employee_id' => '1101',
                'departamento' => 'Dirección',
                'puesto' => 'Socio Inversionista',
                'role' => 'socio',
                'es_socio_override' => true, // forzado a socio
            ],
            [
                'name' => 'Eduardo Pinto',
                'email' => 'epinto@gptservices.com',
                'employee_id' => '1102',
                'departamento' => 'Dirección',
                'puesto' => 'Miembro Comité de Socios',
                'role' => 'comite_socios',
                'es_socio_override' => true,
            ],

            // — Comercial ———————————————————————————————————————
            [
                'name' => 'Patricia Martínez',
                'email' => 'pmartinez@gptservices.com',
                'employee_id' => '1012',
                'departamento' => 'Comercial',
                'puesto' => 'Director de Desarrollo de Negocios',
                'role' => 'director_dn',
            ],
            [
                'name' => 'Andrea Salinas',
                'email' => 'asalinas@gptservices.com',
                'employee_id' => '1201',
                'departamento' => 'Comercial',
                'puesto' => 'Ejecutivo Comercial',
                'role' => 'comercial',
            ],

            // — Proyectos (D13 — los nuevos roles del procedimiento) ——
            [
                'name' => 'Fernando Basave Arce',
                'email' => 'fbasave@gptservices.com',
                'employee_id' => '1001',
                'departamento' => 'Proyectos',
                'puesto' => 'Gerente de Proyectos',
                'role' => 'gerente_proyectos',
            ],
            [
                'name' => 'Mario López',
                'email' => 'mlopez@gptservices.com',
                'employee_id' => '1008',
                'departamento' => 'Proyectos',
                'puesto' => 'Ingeniero de Costos',
                'role' => 'ingeniero_costos',
            ],
            [
                'name' => 'Juan Sánchez',
                'email' => 'jsanchez@gptservices.com',
                'employee_id' => '1009',
                'departamento' => 'Proyectos',
                'puesto' => 'Ingeniero de Proyectos',
                'role' => 'ingeniero_proyectos',
            ],
            [
                'name' => 'Laura Vázquez',
                'email' => 'lvazquez@gptservices.com',
                'employee_id' => '1010',
                'departamento' => 'Proyectos',
                'puesto' => 'Trainee de Proyectos',
                'role' => 'trainee_proyectos',
            ],

            // — Operaciones ———————————————————————————————————
            [
                'name' => 'Roberto García',
                'email' => 'rgarcia@gptservices.com',
                'employee_id' => '1011',
                'departamento' => 'Operaciones',
                'puesto' => 'Gerente de Operaciones',
                'role' => 'gerente_operaciones',
            ],
            [
                'name' => 'Carlos Hernández',
                'email' => 'chernandez@gptservices.com',
                'employee_id' => '1301',
                'departamento' => 'Operaciones',
                'puesto' => 'Servicios Técnicos',
                'role' => 'serv_tecnicos',
            ],
            [
                'name' => 'Jesús Becerra Yebra',
                'email' => 'jbecerra@gptservices.com',
                'employee_id' => '1004',
                'departamento' => 'Operaciones',
                'puesto' => 'Soldadura',
                'role' => 'soldadura',
            ],
            [
                'name' => 'Ana Lilia López Arreola',
                'email' => 'alopez@gptservices.com',
                'employee_id' => '1005',
                'departamento' => 'Operaciones',
                'puesto' => 'Servicios Generales',
                'role' => 'serv_generales',
            ],
            [
                'name' => 'Sergio Ordaz',
                'email' => 'sordaz@gptservices.com',
                'employee_id' => '1007',
                'departamento' => 'Operaciones',
                'puesto' => 'Servicios Generales',
                'role' => 'serv_generales',
            ],
            [
                'name' => 'Erick Daniel Morales Llerena',
                'email' => 'emorales@gptservices.com',
                'employee_id' => '1003',
                'departamento' => 'Operaciones',
                'puesto' => 'QHSE',
                'role' => 'qhse',
            ],
            [
                'name' => 'Pedro Ruiz',
                'email' => 'pruiz@gptservices.com',
                'employee_id' => '1302',
                'departamento' => 'Operaciones',
                'puesto' => 'Almacén',
                'role' => 'almacen',
            ],
            [
                'name' => 'Manuel Torres',
                'email' => 'mtorres@gptservices.com',
                'employee_id' => '1303',
                'departamento' => 'Manufactura',
                'puesto' => 'Manufactura',
                'role' => 'manufactura',
            ],

            // — Compras e Ingeniería —————————————————————————————
            [
                'name' => 'Sofía Mendoza',
                'email' => 'smendoza@gptservices.com',
                'employee_id' => '1401',
                'departamento' => 'Compras',
                'puesto' => 'Compras',
                'role' => 'compras',
            ],
            [
                'name' => 'Roberto Castillo',
                'email' => 'rcastillo@gptservices.com',
                'employee_id' => '1402',
                'departamento' => 'Ingeniería',
                'puesto' => 'Ingeniería de Diseño',
                'role' => 'ingenieria_diseño',
            ],

            // — Finanzas ——————————————————————————————————————
            [
                'name' => 'Denisse Ramírez',
                'email' => 'dramirez@gptservices.com',
                'employee_id' => '1006',
                'departamento' => 'Finanzas',
                'puesto' => 'CFO',
                'role' => 'cfo',
            ],
            [
                'name' => 'Lucía Ramos',
                'email' => 'lramos@gptservices.com',
                'employee_id' => '1501',
                'departamento' => 'Finanzas',
                'puesto' => 'Analista Financiero',
                'role' => 'analista_financiero',
            ],
            [
                'name' => 'Diego Vega',
                'email' => 'dvega@gptservices.com',
                'employee_id' => '1502',
                'departamento' => 'Finanzas',
                'puesto' => 'Auxiliar de Finanzas',
                'role' => 'finanzas_general',
            ],

            // — Externos ——————————————————————————————————————
            [
                'name' => 'Ricardo Auditor',
                'email' => 'auditor@kpmg.example',
                'departamento' => 'Auditoría externa',
                'puesto' => 'Auditor externo',
                'role' => 'auditor_externo',
                'allowlist_provider' => 'google', // entra con Google
            ],
            [
                'name' => 'Cliente IGASAMEX',
                'email' => 'pm@igasamex.example',
                'departamento' => 'IGASAMEX',
                'puesto' => 'Project Manager',
                'role' => 'cliente_externo',
                'allowlist_provider' => 'microsoft',
            ],
            [
                'name' => 'Inversionista Externo',
                'email' => 'inversor@familyoffice.example',
                'departamento' => 'Externo',
                'puesto' => 'Socio de Capital',
                'role' => 'socio',
                'socio_allowlist' => true, // socio vía allowlist (no override, no RH)
            ],

            // — Caso especial: usuario suspendido ————————————————
            [
                'name' => 'Usuario Suspendido',
                'email' => 'suspendido@gptservices.com',
                'employee_id' => '9001',
                'departamento' => 'Proyectos',
                'puesto' => 'Ingeniero de Proyectos',
                'role' => 'ingeniero_proyectos',
                'status' => 'suspended',
            ],
        ];

        foreach ($users as $row) {
            $user = User::updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => $passwordHash,
                    'employee_id' => $row['employee_id'] ?? null,
                    'departamento' => $row['departamento'] ?? null,
                    'puesto' => $row['puesto'] ?? null,
                    'es_socio_override' => $row['es_socio_override'] ?? null,
                    'status' => $row['status'] ?? 'active',
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles([$row['role']]);

            AuthProvider::updateOrCreate(
                ['provider' => 'email_password', 'provider_user_id' => (string) $user->id],
                [
                    'user_id' => $user->id,
                    'password_hash' => $passwordHash,
                    'is_primary' => true,
                    'linked_at' => now(),
                ],
            );

            // Si es externo con login social, lo metemos al email_allowlist
            if (! empty($row['allowlist_provider'])) {
                EmailAllowlist::updateOrCreate(
                    ['email' => $row['email']],
                    [
                        'allowed_providers' => [$row['allowlist_provider']],
                        'role_default' => $row['role'],
                        'departamento_default' => $row['departamento'] ?? null,
                    ],
                );
            }

            // Si es socio vía allowlist, lo agregamos a socios_allowlist
            if (! empty($row['socio_allowlist'])) {
                SocioAllowlist::updateOrCreate(
                    ['email' => $row['email']],
                    ['notes' => 'Socio externo registrado vía allowlist (D2)'],
                );
            }
        }

        $this->command?->info('TestUsersSeeder: '.count($users).' usuarios creados/actualizados (password: password).');
    }
}
