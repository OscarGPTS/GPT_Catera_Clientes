<?php

namespace App\Services\Proyectos;

use App\Models\Secuencia;
use Illuminate\Support\Facades\DB;

/**
 * Asignación atómica de CP-XXX/AA y DN-XXX/AA con lockForUpdate.
 */
class SecuenciasService
{
    public function asignarCp(int $año): string
    {
        return $this->siguiente('cp', $año, 'CP');
    }

    public function asignarDn(int $año): string
    {
        return $this->siguiente('dn', $año, 'DN');
    }

    private function siguiente(string $tipo, int $año, string $prefijo): string
    {
        return DB::transaction(function () use ($tipo, $año, $prefijo) {
            $sec = Secuencia::where('tipo', $tipo)->where('año', $año)->lockForUpdate()->first();

            if (! $sec) {
                $sec = Secuencia::create([
                    'tipo' => $tipo,
                    'año' => $año,
                    'ultimo_consecutivo' => 0,
                ]);
                $sec = Secuencia::where('id', $sec->id)->lockForUpdate()->first();
            }

            $sec->increment('ultimo_consecutivo');

            return sprintf('%s-%03d/%02d', $prefijo, $sec->ultimo_consecutivo, $año % 100);
        });
    }
}
