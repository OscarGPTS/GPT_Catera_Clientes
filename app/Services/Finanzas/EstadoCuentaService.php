<?php

namespace App\Services\Finanzas;

use App\Models\CuentaBancaria;
use App\Models\EstadoCuenta;
use App\Services\Finanzas\Parsers\BanorteParser;
use App\Services\Finanzas\Parsers\BbvaParser;
use App\Services\Finanzas\Parsers\EstadoCuentaParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EstadoCuentaService
{
    public function __construct(private readonly FinanzasAuditService $audit) {}

    public function importar(CuentaBancaria $cuenta, int $mes, int $año, ?UploadedFile $archivo, int $userId): EstadoCuenta
    {
        if ($mes < 1 || $mes > 12) {
            throw new RuntimeException('Mes inválido.');
        }

        return DB::transaction(function () use ($cuenta, $mes, $año, $archivo, $userId) {
            $estado = EstadoCuenta::firstOrCreate(
                ['cuenta_id' => $cuenta->id, 'mes' => $mes, 'año' => $año],
                ['total_movimientos' => 0],
            );

            if ($archivo) {
                $folder = "estados_cuenta/{$cuenta->id}";
                Storage::disk('local')->makeDirectory($folder);
                $path = $archivo->storeAs($folder, $archivo->getClientOriginalName(), 'local');

                $estado->update(['archivo_origen_path' => $path]);

                // Parsear y persistir movimientos
                $parser = $this->parserPara($cuenta->banco);
                $movimientos = $parser->parse(Storage::disk('local')->path($path));

                $estado->movimientos()->delete(); // re-importar reemplaza
                foreach ($movimientos as $mov) {
                    $estado->movimientos()->create($mov);
                }

                $estado->update([
                    'total_movimientos' => count($movimientos),
                    'parseado_at' => now(),
                ]);

                $this->audit->registrar(
                    'estado_cuenta_importado',
                    $estado,
                    ['movimientos' => count($movimientos), 'archivo' => $archivo->getClientOriginalName()],
                    $userId,
                );
            }

            return $estado->fresh('movimientos');
        });
    }

    public function parserPara(string $banco): EstadoCuentaParser
    {
        return match ($banco) {
            'bbva' => app(BbvaParser::class),
            'banorte' => app(BanorteParser::class),
            default => throw new RuntimeException("No hay parser implementado para banco: {$banco}. TODO M11b."),
        };
    }
}
