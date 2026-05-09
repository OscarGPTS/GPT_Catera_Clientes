<?php

namespace App\Services\Libro;

use App\Models\LibroDocumento;
use App\Models\LibroProyecto;
use App\Models\LibroSeccion;
use App\Models\LibroSeccionChecklist;
use App\Models\SystemSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Operaciones del Libro de Proyecto: marcar items, agregar items custom,
 * subir documentos, asignar responsables y evaluar D11 (bloqueo de cierre
 * cuando el dossier no está al 100%).
 */
class LibroService
{
    public function toggleChecklistItem(LibroSeccionChecklist $item, int $userId): LibroSeccionChecklist
    {
        $item->update([
            'completado' => ! $item->completado,
            'completado_por_id' => ! $item->completado ? $userId : null,
            'completado_at' => ! $item->completado ? now() : null,
        ]);

        $item->seccion->recalcularAvance();

        return $item->fresh();
    }

    public function agregarItem(LibroSeccion $seccion, string $descripcion): LibroSeccionChecklist
    {
        $item = $seccion->checklist()->create([
            'item_descripcion' => $descripcion,
            'completado' => false,
        ]);

        $seccion->recalcularAvance();

        return $item;
    }

    public function eliminarItem(LibroSeccionChecklist $item): void
    {
        $seccion = $item->seccion;
        $item->delete();
        $seccion->recalcularAvance();
    }

    public function actualizarSeccion(LibroSeccion $seccion, array $data): LibroSeccion
    {
        $seccion->update(array_filter([
            'descripcion' => $data['descripcion'] ?? null,
            'observaciones' => $data['observaciones'] ?? null,
            'responsable_id' => array_key_exists('responsable_id', $data) ? $data['responsable_id'] : null,
        ], fn ($v) => $v !== null || array_key_exists('responsable_id', $data)));

        return $seccion->fresh();
    }

    public function subirDocumento(LibroSeccion $seccion, UploadedFile $file, int $userId, ?int $linkChecklistId = null): LibroDocumento
    {
        $folder = "libro/{$seccion->libro_id}/seccion-{$seccion->codigo}";
        Storage::disk('local')->makeDirectory($folder);

        $path = $file->storeAs($folder, $file->getClientOriginalName(), 'local');

        $version = $seccion->documentos()
            ->where('nombre', $file->getClientOriginalName())
            ->max('version') ?? 0;

        $doc = $seccion->documentos()->create([
            'nombre' => $file->getClientOriginalName(),
            'archivo_path' => $path,
            'version' => $version + 1,
            'mime_type' => $file->getClientMimeType(),
            'tamaño' => $file->getSize(),
            'subido_por_id' => $userId,
            'subido_at' => now(),
        ]);

        if ($linkChecklistId) {
            $item = $seccion->checklist()->where('id', $linkChecklistId)->first();
            if ($item) {
                $item->update([
                    'evidencia_documento_id' => $doc->id,
                    'completado' => true,
                    'completado_por_id' => $userId,
                    'completado_at' => now(),
                ]);
                $seccion->recalcularAvance();
            }
        }

        return $doc;
    }

    public function eliminarDocumento(LibroDocumento $doc): void
    {
        if (Storage::disk('local')->exists($doc->archivo_path)) {
            Storage::disk('local')->delete($doc->archivo_path);
        }

        // Limpiar referencias en checklist
        LibroSeccionChecklist::where('evidencia_documento_id', $doc->id)
            ->update(['evidencia_documento_id' => null]);

        $seccion = $doc->seccion;
        $doc->delete();
        $seccion?->recalcularAvance();
    }

    /**
     * D11. Evalúa si el libro permite cierre. Si el setting
     * `bloqueo_cierre_dossier_incompleto` es true, exige 100% global.
     * Devuelve [bloqueado: bool, razones: string[]].
     */
    public function evaluarBloqueoCierre(LibroProyecto $libro): array
    {
        $reglaActiva = (bool) SystemSetting::get('bloqueo_cierre_dossier_incompleto', true);
        if (! $reglaActiva) {
            return ['bloqueado' => false, 'razones' => []];
        }

        $razones = [];
        $libro->loadMissing('secciones');

        foreach ($libro->secciones as $seccion) {
            if ((float) $seccion->porcentaje_avance < 100) {
                $razones[] = sprintf(
                    'Sección %s — %s al %s%%',
                    $seccion->codigo,
                    $seccion->nombre,
                    number_format((float) $seccion->porcentaje_avance, 1),
                );
            }
        }

        return [
            'bloqueado' => count($razones) > 0,
            'razones' => $razones,
        ];
    }

    public function actualizarBloqueoCierre(LibroProyecto $libro): LibroProyecto
    {
        $eval = $this->evaluarBloqueoCierre($libro);
        $libro->update(['bloqueado_para_cierre' => $eval['bloqueado']]);

        return $libro->fresh();
    }

    public function descargarDocumento(LibroDocumento $doc)
    {
        if (! Storage::disk('local')->exists($doc->archivo_path)) {
            throw new RuntimeException('Archivo no encontrado.');
        }

        return Storage::disk('local')->download($doc->archivo_path, $doc->nombre);
    }
}
