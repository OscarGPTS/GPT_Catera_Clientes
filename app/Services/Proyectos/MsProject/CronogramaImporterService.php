<?php

namespace App\Services\Proyectos\MsProject;

use App\Models\Cronograma;
use App\Models\Proyecto;
use App\Services\Proyectos\CronogramaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Importa actividades desde MS Project (.xml o .csv) creando una nueva versión
 * del cronograma. El .mpp binario no se soporta nativamente — requiere mpxj-cli
 * (TODO M5b ext).
 */
class CronogramaImporterService
{
    public function __construct(private readonly CronogramaService $cronogramaService) {}

    public function importar(Proyecto $proyecto, UploadedFile $archivo, int $userId): Cronograma
    {
        if (! in_array($proyecto->estado, ['adjudicado_firmado', 'en_ejecucion', 'en_cierre', 'cerrado'], true)) {
            throw new RuntimeException("El cronograma se levanta tras la adjudicación firmada. Estado actual: {$proyecto->estado}.");
        }

        $extension = strtolower($archivo->getClientOriginalExtension());
        $parser = $this->parserPara($extension);

        $folder = "cronogramas/{$proyecto->id}";
        Storage::disk('local')->makeDirectory($folder);
        $path = $archivo->storeAs($folder, $archivo->getClientOriginalName(), 'local');

        $actividades = $parser->parse(Storage::disk('local')->path($path));

        return DB::transaction(function () use ($proyecto, $userId, $path, $actividades) {
            $cronograma = $this->cronogramaService->crearVersion($proyecto, $userId);

            // Limpiar las actividades copiadas de la versión anterior
            $cronograma->actividades()->delete();

            $cronograma->update(['archivo_origen_path' => $path]);

            foreach ($actividades as $act) {
                $this->cronogramaService->agregarActividad($cronograma, [
                    'codigo' => $act['codigo'],
                    'nombre' => $act['nombre'],
                    'fecha_inicio_planeada' => $act['fecha_inicio_planeada'],
                    'fecha_fin_planeada' => $act['fecha_fin_planeada'],
                    'porcentaje_avance' => $act['porcentaje_avance'],
                    'predecesoras' => $act['predecesoras'],
                ]);
            }

            $proyecto->recordEvent(
                tipo: 'cronograma_importado',
                userId: $userId,
                payload: [
                    'cronograma_id' => $cronograma->id,
                    'actividades' => count($actividades),
                    'archivo' => basename($path),
                ],
            );

            return $cronograma->fresh('actividades');
        });
    }

    private function parserPara(string $extension): MsProjectParser
    {
        return match ($extension) {
            'xml' => app(MsProjectXmlParser::class),
            'csv', 'txt' => app(MsProjectCsvParser::class),
            'mpp' => throw new RuntimeException('Formato .mpp binario no soportado nativamente. Exporta el archivo a XML o CSV desde MS Project (File → Save As → XML).'),
            default => throw new RuntimeException("Extensión '{$extension}' no soportada. Usa .xml o .csv."),
        };
    }
}
