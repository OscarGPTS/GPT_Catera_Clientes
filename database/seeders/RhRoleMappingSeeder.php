<?php

namespace Database\Seeders;

use App\Models\RhRoleMapping;
use Illuminate\Database\Seeder;

class RhRoleMappingSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            ['puesto_rh' => '%Director General%', 'rol_sistema' => 'direccion_general', 'prioridad' => 100],
            ['puesto_rh' => '%Director%Desarrollo de Negocios%', 'rol_sistema' => 'director_dn', 'prioridad' => 90],
            ['puesto_rh' => '%CFO%', 'rol_sistema' => 'cfo', 'prioridad' => 90],
            ['puesto_rh' => '%Director%Finanzas%', 'rol_sistema' => 'cfo', 'prioridad' => 90],
            ['puesto_rh' => '%Gerente de Proyectos%', 'rol_sistema' => 'gerente_proyectos', 'prioridad' => 80],
            ['puesto_rh' => '%Gerente de Operaciones%', 'rol_sistema' => 'gerente_operaciones', 'prioridad' => 80],
            ['puesto_rh' => '%Ingeniero de Costos%', 'rol_sistema' => 'ingeniero_costos', 'prioridad' => 70],
            ['puesto_rh' => '%Ingeniero de Proyectos%', 'rol_sistema' => 'ingeniero_proyectos', 'prioridad' => 60],
            ['puesto_rh' => '%Trainee%', 'rol_sistema' => 'trainee_proyectos', 'prioridad' => 50],
            ['puesto_rh' => '%Becario%', 'rol_sistema' => 'trainee_proyectos', 'prioridad' => 50],
            ['puesto_rh' => '%Comercial%', 'rol_sistema' => 'comercial', 'prioridad' => 60, 'departamento_filter' => 'Comercial'],
            ['puesto_rh' => '%Ventas%', 'rol_sistema' => 'comercial', 'prioridad' => 60],
            ['puesto_rh' => '%QHSE%', 'rol_sistema' => 'qhse', 'prioridad' => 70],
            ['puesto_rh' => '%Soldadura%', 'rol_sistema' => 'soldadura', 'prioridad' => 70],
            ['puesto_rh' => '%Servicios Generales%', 'rol_sistema' => 'serv_generales', 'prioridad' => 70],
            ['puesto_rh' => '%Servicios Técnicos%', 'rol_sistema' => 'serv_tecnicos', 'prioridad' => 70],
            ['puesto_rh' => '%Almacén%', 'rol_sistema' => 'almacen', 'prioridad' => 70],
            ['puesto_rh' => '%Almacen%', 'rol_sistema' => 'almacen', 'prioridad' => 70],
            ['puesto_rh' => '%Manufactura%', 'rol_sistema' => 'manufactura', 'prioridad' => 70],
            ['puesto_rh' => '%Compras%', 'rol_sistema' => 'compras', 'prioridad' => 70],
            ['puesto_rh' => '%Ingeniería%Diseño%', 'rol_sistema' => 'ingenieria_diseño', 'prioridad' => 70],
        ];

        foreach ($rules as $rule) {
            RhRoleMapping::updateOrCreate(
                ['puesto_rh' => $rule['puesto_rh'], 'rol_sistema' => $rule['rol_sistema']],
                $rule + ['activo' => true],
            );
        }
    }
}
