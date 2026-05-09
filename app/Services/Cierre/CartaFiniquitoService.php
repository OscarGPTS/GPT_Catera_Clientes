<?php

namespace App\Services\Cierre;

use App\Models\CartaFiniquito;
use App\Models\Proyecto;
use App\Models\SystemSetting;
use App\Services\Libro\LibroService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Carta Finiquito CP→DN (FO-GPT-PYT-CF-01).
 *
 * Reglas D11/D12:
 *  - bloqueo_cierre_dossier_incompleto = true: el libro debe estar al 100%.
 *  - bloqueo_cierre_post_mortem_pendiente = true: el post-mortem debe existir antes
 *    de transicionar a estado 'cerrado'.
 *
 * El cierre formal mueve el proyecto de 'en_cierre' → 'cerrado'.
 */
class CartaFiniquitoService
{
    public function __construct(private readonly LibroService $libroService) {}

    public function crearOActualizar(Proyecto $proyecto, int $userId, array $data): CartaFiniquito
    {
        if (! in_array($proyecto->estado, ['en_cierre', 'cerrado'], true)) {
            throw new RuntimeException("La carta finiquito se levanta cuando el proyecto está en cierre. Estado actual: {$proyecto->estado}.");
        }

        return DB::transaction(function () use ($proyecto, $userId, $data) {
            $carta = $proyecto->cartaFiniquito;

            if (! $carta) {
                $carta = $proyecto->cartaFiniquito()->create([
                    'fecha_emision' => $data['fecha_emision'] ?? now()->toDateString(),
                    'personal_liberado' => $this->limpiar($data['personal_liberado'] ?? []),
                    'equipos_liberados' => $this->limpiar($data['equipos_liberados'] ?? []),
                    'observaciones' => $data['observaciones'] ?? null,
                ]);

                $proyecto->recordEvent(
                    tipo: 'carta_finiquito_creada',
                    userId: $userId,
                    payload: ['carta_id' => $carta->id],
                );
            } else {
                if ($carta->firmado_cliente_at && $carta->firmado_gpt_at) {
                    throw new RuntimeException('La carta ya está firmada por ambas partes.');
                }

                $carta->update([
                    'fecha_emision' => $data['fecha_emision'] ?? $carta->fecha_emision,
                    'personal_liberado' => $this->limpiar($data['personal_liberado'] ?? $carta->personal_liberado ?? []),
                    'equipos_liberados' => $this->limpiar($data['equipos_liberados'] ?? $carta->equipos_liberados ?? []),
                    'observaciones' => $data['observaciones'] ?? $carta->observaciones,
                ]);
            }

            return $carta->fresh();
        });
    }

    public function firmarGpt(CartaFiniquito $carta, int $userId): CartaFiniquito
    {
        if ($carta->firmado_gpt_at) {
            return $carta;
        }

        $bloqueo = $this->libroService->evaluarBloqueoCierre($carta->proyecto->libro);
        if ($bloqueo['bloqueado']) {
            throw new RuntimeException('D11: el libro de proyecto no está al 100%. '.implode(' · ', $bloqueo['razones']));
        }

        $carta->update(['firmado_gpt_at' => now()]);

        $carta->proyecto->recordEvent(
            tipo: 'carta_finiquito_firmada_gpt',
            userId: $userId,
            payload: ['carta_id' => $carta->id],
        );

        return $carta->fresh();
    }

    public function firmarCliente(CartaFiniquito $carta, int $userId, string $nombreCliente): CartaFiniquito
    {
        if (! $carta->firmado_gpt_at) {
            throw new RuntimeException('GPT debe firmar antes que el cliente.');
        }

        if ($carta->firmado_cliente_at) {
            return $carta;
        }

        $carta->update(['firmado_cliente_at' => now()]);

        $carta->proyecto->recordEvent(
            tipo: 'carta_finiquito_firmada_cliente',
            userId: $userId,
            payload: ['carta_id' => $carta->id, 'cliente_nombre' => $nombreCliente],
            comentario: "Firmada por {$nombreCliente}",
        );

        return $carta->fresh();
    }

    /**
     * Cierre formal del proyecto. Aplica D11 y D12.
     */
    public function cerrarProyecto(CartaFiniquito $carta, int $userId): Proyecto
    {
        $proyecto = $carta->proyecto;

        if ($proyecto->estado === 'cerrado') {
            return $proyecto;
        }

        if (! $carta->firmado_gpt_at || ! $carta->firmado_cliente_at) {
            throw new RuntimeException('La carta finiquito debe estar firmada por GPT y el cliente antes de cerrar.');
        }

        $bloqueo = $this->libroService->evaluarBloqueoCierre($proyecto->libro);
        if ($bloqueo['bloqueado']) {
            throw new RuntimeException('D11: el libro de proyecto no está al 100%.');
        }

        if ((bool) SystemSetting::get('bloqueo_cierre_post_mortem_pendiente', false)) {
            if (! $proyecto->postMortem) {
                throw new RuntimeException('D12: post-mortem requerido antes de cerrar el proyecto.');
            }
        }

        return DB::transaction(function () use ($proyecto, $userId) {
            $estadoAnterior = $proyecto->estado;
            $proyecto->update(['estado' => 'cerrado']);

            $proyecto->recordEvent(
                tipo: 'proyecto_cerrado',
                userId: $userId,
                estadoAnterior: $estadoAnterior,
            );

            return $proyecto->fresh();
        });
    }

    private function limpiar(array $items): array
    {
        return array_values(array_filter($items, fn ($i) => filled(is_array($i) ? ($i['nombre'] ?? null) : $i)));
    }
}
