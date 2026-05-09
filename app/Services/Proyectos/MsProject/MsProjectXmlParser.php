<?php

namespace App\Services\Proyectos\MsProject;

use Illuminate\Support\Carbon;
use RuntimeException;
use SimpleXMLElement;

/**
 * Parser para archivos Project XML (formato nativo MSP "Save As → XML").
 *
 * Schema: cada `<Task>` contiene UID, ID, Name, Start, Finish, PercentComplete, OutlineLevel,
 * y opcionalmente `<PredecessorLink>` con `<PredecessorUID>`. Tasks UID=0 es el resumen del
 * proyecto y se descarta.
 */
class MsProjectXmlParser implements MsProjectParser
{
    public function parse(string $filePath): array
    {
        if (! file_exists($filePath)) {
            throw new RuntimeException("Archivo no encontrado: {$filePath}");
        }

        $contenido = file_get_contents($filePath);
        if ($contenido === false || trim($contenido) === '') {
            throw new RuntimeException('Archivo vacío.');
        }

        // Quitar el namespace para que XPath simple funcione.
        $contenido = preg_replace('/(<\/?)([a-zA-Z0-9]+:)/', '$1', $contenido);
        $contenido = preg_replace('/xmlns="[^"]+"/', '', $contenido, 1);

        $xml = @simplexml_load_string($contenido);
        if ($xml === false) {
            throw new RuntimeException('XML inválido.');
        }

        if (! isset($xml->Tasks) || ! isset($xml->Tasks->Task)) {
            throw new RuntimeException('No se encontraron <Tasks><Task> en el XML.');
        }

        // Mapa UID → ID para resolver predecesoras
        $uidToId = [];
        foreach ($xml->Tasks->Task as $t) {
            $uid = (string) $t->UID;
            $id = (string) $t->ID;
            $uidToId[$uid] = $id;
        }

        $actividades = [];
        foreach ($xml->Tasks->Task as $t) {
            $uid = (string) $t->UID;
            if ($uid === '0') {
                continue; // resumen del proyecto
            }

            $nombre = trim((string) ($t->Name ?? ''));
            if ($nombre === '') {
                continue;
            }

            $predecesoras = $this->extraerPredecesoras($t, $uidToId);

            $actividades[] = [
                'codigo' => (string) ($t->ID ?? '') !== '' ? (string) $t->ID : null,
                'nombre' => $nombre,
                'fecha_inicio_planeada' => $this->normalizarFecha((string) ($t->Start ?? '')),
                'fecha_fin_planeada' => $this->normalizarFecha((string) ($t->Finish ?? '')),
                'porcentaje_avance' => (float) ($t->PercentComplete ?? 0),
                'predecesoras' => $predecesoras,
                'nivel' => (int) ($t->OutlineLevel ?? 1),
            ];
        }

        if (empty($actividades)) {
            throw new RuntimeException('El XML no contiene actividades válidas.');
        }

        return $actividades;
    }

    /**
     * @param  array<string, string>  $uidToId
     * @return array<int, string>|null
     */
    private function extraerPredecesoras(SimpleXMLElement $task, array $uidToId): ?array
    {
        if (! isset($task->PredecessorLink)) {
            return null;
        }

        $ids = [];
        foreach ($task->PredecessorLink as $pl) {
            $uid = (string) ($pl->PredecessorUID ?? '');
            if ($uid !== '' && isset($uidToId[$uid])) {
                $ids[] = $uidToId[$uid];
            }
        }

        return $ids ?: null;
    }

    private function normalizarFecha(string $raw): ?string
    {
        $clean = trim($raw);
        if ($clean === '') {
            return null;
        }

        try {
            return Carbon::parse($clean)->toDateString();
        } catch (\Exception) {
            return null;
        }
    }
}
