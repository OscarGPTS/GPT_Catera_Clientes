<?php

namespace App\Services\Libro;

use App\Models\LibroProyecto;
use App\Models\LibroSeccion;
use App\Models\Proyecto;
use Illuminate\Support\Facades\DB;

/**
 * D11. Abre el Libro de Proyecto con las 10 secciones A-J obligatorias y el
 * checklist por sublínea (HTP, LSP, VLV, SOL, SG).
 */
class AperturaLibroService
{
    /** @var array<string, string> */
    private const SECCIONES = [
        'A' => 'Cronograma de Actividades',
        'B' => 'Ingeniería de Proyecto',
        'C' => 'Permisos',
        'D' => 'Estudios (memorias de cálculo, plan de calidad)',
        'E' => 'Procedimientos',
        'F' => 'Certificados (personal, equipos, accesorios, materiales)',
        'G' => 'Registro de Pruebas (NDT, hidrostática, hermeticidad)',
        'H' => 'Seguridad (IMSS, DC-3, AST, plan emergencias)',
        'I' => 'Ejecución (bitácoras, reportes)',
        'J' => 'Misceláneos (BOM/BOE, oficios, organigramas)',
    ];

    public function abrirParaProyecto(Proyecto $proyecto): LibroProyecto
    {
        return DB::transaction(function () use ($proyecto) {
            if ($libro = LibroProyecto::where('proyecto_id', $proyecto->id)->first()) {
                return $libro;
            }

            $libro = LibroProyecto::create([
                'proyecto_id' => $proyecto->id,
                'fecha_apertura' => now(),
                'fecha_cierre_estimado' => $proyecto->fecha_fin_planeada,
                'porcentaje_avance_global' => 0,
                'bloqueado_para_cierre' => true,
            ]);

            $sublineaCodigo = $proyecto->sublinea?->codigo ?? 'SG';
            $checklistPlantilla = $this->checklistPorSublinea($sublineaCodigo);

            foreach (self::SECCIONES as $codigo => $nombre) {
                $seccion = LibroSeccion::create([
                    'libro_id' => $libro->id,
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                    'porcentaje_avance' => 0,
                    'estado' => 'pendiente',
                ]);

                foreach ($checklistPlantilla[$codigo] ?? [] as $item) {
                    $seccion->checklist()->create([
                        'item_descripcion' => $item,
                        'completado' => false,
                    ]);
                }
            }

            return $libro;
        });
    }

    /**
     * Plantilla mínima por sublínea (extensible vía LibroPlantillaSeeder en el futuro).
     *
     * @return array<string, array<int, string>>
     */
    private function checklistPorSublinea(string $codigo): array
    {
        $base = [
            'A' => ['Cronograma firmado', 'Ruta crítica identificada'],
            'B' => ['Memorias de cálculo', 'Planos de ingeniería'],
            'C' => ['Permisos del cliente', 'Permisos QHSE'],
            'D' => ['Plan de calidad', 'Estudios previos'],
            'E' => ['Procedimientos aprobados'],
            'F' => ['Certificados de equipos en obra'],
            'G' => ['Pruebas no destructivas (RT/UT/MT/PT)'],
            'H' => ['IMSS vigente', 'DC-3', 'AST', 'Plan de emergencias'],
            'I' => ['Bitácoras diarias', 'Reportes semanales'],
            'J' => ['Organigramas', 'Oficios'],
        ];

        return match ($codigo) {
            'HTP' => array_merge_recursive($base, [
                'F' => ['Certificados de soldadores asignados', 'Certificados de operadores Hot Tap (T-101, TM-760, TM-1200)', 'Certificados de materiales (válvulas, bridas, accesorios)'],
                'G' => ['Prueba de hermeticidad de Hot Tap', 'Lectura y avance de Hot Tap'],
            ]),
            'LSP' => array_merge_recursive($base, [
                'F' => ['Certificados de operadores Line Stop', 'Certificados de equipos LSP'],
                'G' => ['Prueba de hermeticidad de LSP'],
            ]),
            'VLV' => array_merge_recursive($base, [
                'F' => ['Certificados de válvulas', 'Certificados de bridas y accesorios'],
                'G' => ['Pruebas hidrostáticas'],
            ]),
            'SOL' => array_merge_recursive($base, [
                'F' => ['Certificados de soldadores', 'WPS / PQR aplicables'],
                'G' => ['Inspección NDT (RT/UT/MT/PT)'],
            ]),
            default => $base,
        };
    }
}
