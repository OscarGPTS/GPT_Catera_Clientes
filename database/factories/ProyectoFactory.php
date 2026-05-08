<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Proyecto;
use App\Models\Sublinea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proyecto>
 */
class ProyectoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::inRandomOrder()->value('id') ?? Cliente::factory(),
            'sublinea_id' => Sublinea::inRandomOrder()->value('id') ?? Sublinea::factory(),
            'año' => now()->year,
            'usuario_final' => fake()->company(),
            'sector' => fake()->randomElement(['Energía', 'Gobierno', 'Industria', 'Manufactura']),
            'estado' => 'en_revision',
            'resumen_ejecutivo' => fake()->paragraph(3),
            'fecha_inicio_planeada' => fake()->dateTimeBetween('-1 month', '+2 months'),
            'fecha_fin_planeada' => fake()->dateTimeBetween('+3 months', '+10 months'),
            'metodo_distribucion_plurianual' => 'dias_naturales',
            'monto_preliminar' => fake()->randomFloat(2, 50_000, 2_500_000),
            'moneda' => fake()->randomElement(['USD', 'MXN']),
        ];
    }

    public function estado(string $estado): self
    {
        return $this->state(['estado' => $estado]);
    }
}
