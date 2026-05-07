<?php

namespace App\Services\Cotizaciones;

use App\ValueObjects\ResultadoCoss;
use InvalidArgumentException;

/**
 * Calculadora COSS — replica las fórmulas declaradas en el plan ejecutable § 5.12.
 *
 * Fórmulas:
 *   costo_directo = sum(partidas.costo_total)
 *   indirectos    = costo_directo * factor_indirectos
 *   admin         = (costo_directo + indirectos) * factor_admin
 *   base          = costo_directo + indirectos + admin
 *   utilidad      = base * factor_utilidad
 *   precio_venta  = base + utilidad
 *   margen_neto   = utilidad / precio_venta
 *
 * TODO M3: validar al centavo contra `FO-GPT-VTS-01-F` con fixture Texmelucan
 * (cd $61,717.61 → pv $96,998.30, margen 41.59%) cuando el Excel original esté disponible.
 * Si la fórmula real difiere de la del plan, ajustar este servicio antes del Hito 3.
 */
class CalculadoraCoss
{
    /**
     * @param  array<int, array{cantidad?: float|int, costo_unitario?: float, costo_total?: float}>  $partidas
     * @param  array{indirectos?: float, admin?: float, utilidad?: float}  $factores
     */
    public function calcular(array $partidas, array $factores): ResultadoCoss
    {
        $factIndirectos = (float) ($factores['indirectos'] ?? 0);
        $factAdmin = (float) ($factores['admin'] ?? 0);
        $factUtilidad = (float) ($factores['utilidad'] ?? 0);

        if ($factIndirectos < 0 || $factAdmin < 0 || $factUtilidad < 0) {
            throw new InvalidArgumentException('Los factores no pueden ser negativos.');
        }

        $costoDirecto = 0.0;
        foreach ($partidas as $p) {
            if (isset($p['costo_total'])) {
                $costoDirecto += (float) $p['costo_total'];
            } else {
                $costoDirecto += (float) ($p['cantidad'] ?? 1) * (float) ($p['costo_unitario'] ?? 0);
            }
        }

        $indirectos = $costoDirecto * $factIndirectos;
        $admin = ($costoDirecto + $indirectos) * $factAdmin;
        $base = $costoDirecto + $indirectos + $admin;
        $utilidad = $base * $factUtilidad;
        $precioVenta = $base + $utilidad;
        $margenNeto = $precioVenta > 0 ? $utilidad / $precioVenta : 0.0;

        return new ResultadoCoss(
            costoDirecto: $costoDirecto,
            indirectos: $indirectos,
            admin: $admin,
            base: $base,
            utilidad: $utilidad,
            precioVenta: $precioVenta,
            margenNeto: $margenNeto,
            factorIndirectos: $factIndirectos,
            factorAdmin: $factAdmin,
            factorUtilidad: $factUtilidad,
        );
    }
}
