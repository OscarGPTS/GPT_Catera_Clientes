<?php

namespace App\ValueObjects;

class ResultadoCoss
{
    public function __construct(
        public readonly float $costoDirecto,
        public readonly float $indirectos,
        public readonly float $admin,
        public readonly float $base,
        public readonly float $utilidad,
        public readonly float $precioVenta,
        public readonly float $margenNeto,
        public readonly float $factorIndirectos,
        public readonly float $factorAdmin,
        public readonly float $factorUtilidad,
    ) {}

    public function toArray(): array
    {
        return [
            'costo_directo' => round($this->costoDirecto, 2),
            'indirectos' => round($this->indirectos, 2),
            'admin' => round($this->admin, 2),
            'base' => round($this->base, 2),
            'utilidad' => round($this->utilidad, 2),
            'precio_venta' => round($this->precioVenta, 2),
            'margen_neto' => round($this->margenNeto, 4),
            'factor_indirectos' => round($this->factorIndirectos, 4),
            'factor_admin' => round($this->factorAdmin, 4),
            'factor_utilidad' => round($this->factorUtilidad, 4),
        ];
    }
}
