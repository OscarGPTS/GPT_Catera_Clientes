<?php

namespace App\Services\Rh;

use App\DTOs\RhUser;
use Illuminate\Support\Collection;

class RhClientMock implements RhClientInterface
{
    /** @var array<int, array<string, mixed>> */
    private array $fixtures = [
        [
            'employee_id' => '1001',
            'email' => 'fbasave@gptservices.com',
            'name' => 'Fernando Basave Arce',
            'puesto' => 'Gerente de Proyectos',
            'departamento' => 'Proyectos',
            'fecha_ingreso' => '2018-03-12',
            'gerencia_regional' => 'GRC',
        ],
        [
            'employee_id' => '1002',
            'email' => 'gguterrez@gptservices.com',
            'name' => 'Guillermo Gutiérrez Melo',
            'puesto' => 'Director General',
            'departamento' => 'Dirección',
            'fecha_ingreso' => '2010-01-15',
            'gerencia_regional' => 'DG',
        ],
        [
            'employee_id' => '1003',
            'email' => 'emorales@gptservices.com',
            'name' => 'Erick Daniel Morales Llerena',
            'puesto' => 'QHSE',
            'departamento' => 'Operaciones',
            'fecha_ingreso' => '2019-07-22',
            'gerencia_regional' => 'GRC',
        ],
        [
            'employee_id' => '1004',
            'email' => 'jbecerra@gptservices.com',
            'name' => 'Jesús Becerra Yebra',
            'puesto' => 'Soldadura',
            'departamento' => 'Operaciones',
            'fecha_ingreso' => '2016-11-08',
            'gerencia_regional' => 'GRS',
        ],
        [
            'employee_id' => '1005',
            'email' => 'alopez@gptservices.com',
            'name' => 'Ana Lilia López Arreola',
            'puesto' => 'Servicios Generales',
            'departamento' => 'Operaciones',
            'fecha_ingreso' => '2017-05-30',
            'gerencia_regional' => 'DG',
        ],
        [
            'employee_id' => '1006',
            'email' => 'dramirez@gptservices.com',
            'name' => 'Denisse Ramírez',
            'puesto' => 'CFO',
            'departamento' => 'Finanzas',
            'fecha_ingreso' => '2020-02-10',
            'gerencia_regional' => 'DG',
        ],
        [
            'employee_id' => '1007',
            'email' => 'sordaz@gptservices.com',
            'name' => 'Sergio Ordaz',
            'puesto' => 'Servicios Generales',
            'departamento' => 'Operaciones',
            'fecha_ingreso' => '2015-09-01',
            'gerencia_regional' => 'GRC',
        ],
        [
            'employee_id' => '1008',
            'email' => 'mlopez@gptservices.com',
            'name' => 'Mario López',
            'puesto' => 'Ingeniero de Costos',
            'departamento' => 'Proyectos',
            'fecha_ingreso' => '2021-04-15',
            'gerencia_regional' => 'GRC',
        ],
        [
            'employee_id' => '1009',
            'email' => 'jsanchez@gptservices.com',
            'name' => 'Juan Sánchez',
            'puesto' => 'Ingeniero de Proyectos',
            'departamento' => 'Proyectos',
            'fecha_ingreso' => '2022-08-20',
            'gerencia_regional' => 'GRS',
        ],
        [
            'employee_id' => '1010',
            'email' => 'lvazquez@gptservices.com',
            'name' => 'Laura Vázquez',
            'puesto' => 'Trainee de Proyectos',
            'departamento' => 'Proyectos',
            'fecha_ingreso' => '2025-01-15',
            'gerencia_regional' => 'GRN',
        ],
        [
            'employee_id' => '1011',
            'email' => 'rgarcia@gptservices.com',
            'name' => 'Roberto García',
            'puesto' => 'Gerente de Operaciones',
            'departamento' => 'Operaciones',
            'fecha_ingreso' => '2014-06-01',
            'gerencia_regional' => 'GRC',
        ],
        [
            'employee_id' => '1012',
            'email' => 'pmartinez@gptservices.com',
            'name' => 'Patricia Martínez',
            'puesto' => 'Director de Desarrollo de Negocios',
            'departamento' => 'Comercial',
            'fecha_ingreso' => '2017-03-15',
            'gerencia_regional' => 'GRC',
        ],
    ];

    public function searchByEmail(string $email): ?RhUser
    {
        foreach ($this->fixtures as $row) {
            if (strcasecmp($row['email'], $email) === 0) {
                return RhUser::fromArray($row);
            }
        }

        return null;
    }

    public function getById(string $employeeId): ?RhUser
    {
        foreach ($this->fixtures as $row) {
            if ((string) $row['employee_id'] === $employeeId) {
                return RhUser::fromArray($row);
            }
        }

        return null;
    }

    public function listAll(): Collection
    {
        return collect($this->fixtures)->map(fn ($row) => RhUser::fromArray($row));
    }
}
