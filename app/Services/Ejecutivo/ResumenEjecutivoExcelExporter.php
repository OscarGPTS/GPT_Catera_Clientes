<?php

namespace App\Services\Ejecutivo;

use App\Models\Proyecto;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Exporta resumen ejecutivo a Excel multi-hoja:
 *   1. KPIs — pipeline, hit rates, alertas de concentración.
 *   2. Proyectos — listado de todos los proyectos del año con sus datos clave.
 *   3. Concentración — desglose de monto pipeline por cliente.
 */
class ResumenEjecutivoExcelExporter
{
    public function __construct(private readonly KpisEjecutivoService $kpis) {}

    public function exportar(int $año): string
    {
        $kpis = $this->kpis->snapshot($año);
        $proyectos = Proyecto::with(['cliente:id,razon_social,alias_3letras', 'sublinea:id,codigo,nombre', 'gerenteProyectos:id,name'])
            ->where('año', $año)
            ->orderBy('estado')
            ->orderBy('cp_numero')
            ->get();

        $spreadsheet = new Spreadsheet;
        $this->hojaKpis($spreadsheet, $año, $kpis);
        $this->hojaProyectos($spreadsheet, $proyectos);
        $this->hojaConcentracion($spreadsheet, $kpis);

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        Storage::disk('local')->makeDirectory('ejecutivo');
        $filename = "ejecutivo/resumen-ejecutivo-{$año}.xlsx";
        $absPath = Storage::disk('local')->path($filename);
        $writer->save($absPath);

        return $filename;
    }

    private function hojaKpis(Spreadsheet $sb, int $año, array $kpis): void
    {
        $sheet = $sb->getActiveSheet();
        $sheet->setTitle('KPIs');

        $sheet->setCellValue('A1', "RESUMEN EJECUTIVO {$año}");
        $sheet->mergeCells('A1:D1');
        $this->styleHeader($sheet, 'A1:D1');

        $rows = [
            ['Indicador', 'Valor', '', ''],
            ['Pipeline (monto)', $kpis['pipeline_monto'], 'MXN/USD', ''],
            ['Pipeline (proyectos)', $kpis['pipeline_conteo'], '', ''],
            ['Adjudicado (monto)', $kpis['adjudicado_monto'], 'MXN/USD', ''],
            ['Adjudicado (proyectos)', $kpis['adjudicado_conteo'], '', ''],
            ['Hit rate por conteo', round($kpis['hit_rate_conteo'] * 100, 2).'%', '', ''],
            ['Hit rate por monto', round($kpis['hit_rate_monto'] * 100, 2).'%', '', ''],
            ['SEDENA share del pipeline', round($kpis['sedena_share'] * 100, 2).'%', '', ''],
            ['Total proyectos del año', $kpis['total_proyectos_año'], '', ''],
            ['Umbral alerta concentración', round($kpis['umbral_alerta'] * 100, 0).'%', '', ''],
        ];

        $row = 3;
        foreach ($rows as $r) {
            $sheet->setCellValue("A{$row}", $r[0]);
            $sheet->setCellValue("B{$row}", $r[1]);
            $sheet->setCellValue("C{$row}", $r[2]);
            $row++;
        }

        $this->styleHeader($sheet, 'A3:D3');

        if (! empty($kpis['alertas_concentracion'])) {
            $row += 2;
            $sheet->setCellValue("A{$row}", 'ALERTAS DE CONCENTRACIÓN');
            $sheet->mergeCells("A{$row}:D{$row}");
            $this->styleHeader($sheet, "A{$row}:D{$row}");
            $row++;

            $sheet->setCellValue("A{$row}", 'Cliente');
            $sheet->setCellValue("B{$row}", 'Monto');
            $sheet->setCellValue("C{$row}", '% pipeline');
            $this->styleHeader($sheet, "A{$row}:D{$row}");
            $row++;

            foreach ($kpis['alertas_concentracion'] as $alerta) {
                $sheet->setCellValue("A{$row}", $alerta['cliente']);
                $sheet->setCellValue("B{$row}", $alerta['monto']);
                $sheet->setCellValue("C{$row}", round($alerta['porcentaje'] * 100, 2).'%');
                $row++;
            }
        }

        $sheet->getColumnDimension('A')->setWidth(34);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(14);
    }

    private function hojaProyectos(Spreadsheet $sb, $proyectos): void
    {
        $sheet = $sb->createSheet();
        $sheet->setTitle('Proyectos');

        $headers = ['CP', 'DN', 'Cliente', 'Sublínea', 'Estado', 'GP', 'Monto', 'Moneda', 'Inicio', 'Fin'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col.'1', $h);
            $col++;
        }
        $this->styleHeader($sheet, 'A1:J1');

        $row = 2;
        foreach ($proyectos as $p) {
            $sheet->setCellValue("A{$row}", $p->cp_numero);
            $sheet->setCellValue("B{$row}", $p->dn_numero);
            $sheet->setCellValue("C{$row}", $p->cliente?->razon_social);
            $sheet->setCellValue("D{$row}", $p->sublinea?->codigo);
            $sheet->setCellValue("E{$row}", $p->estado);
            $sheet->setCellValue("F{$row}", $p->gerenteProyectos?->name);
            $sheet->setCellValue("G{$row}", (float) ($p->monto_preliminar ?? 0));
            $sheet->setCellValue("H{$row}", $p->moneda);
            $sheet->setCellValue("I{$row}", optional($p->fecha_inicio_planeada)->format('Y-m-d'));
            $sheet->setCellValue("J{$row}", optional($p->fecha_fin_planeada)->format('Y-m-d'));
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(28);
        $sheet->getColumnDimension('D')->setWidth(10);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(22);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('I')->setWidth(12);
        $sheet->getColumnDimension('J')->setWidth(12);

        if ($row > 2) {
            $sheet->getStyle('G2:G'.($row - 1))->getNumberFormat()->setFormatCode('"$"#,##0.00');
        }
    }

    private function hojaConcentracion(Spreadsheet $sb, array $kpis): void
    {
        $sheet = $sb->createSheet();
        $sheet->setTitle('Concentración');

        $sheet->setCellValue('A1', 'Cliente');
        $sheet->setCellValue('B1', 'Monto pipeline');
        $sheet->setCellValue('C1', '% del total');
        $this->styleHeader($sheet, 'A1:C1');

        $total = (float) $kpis['pipeline_monto'];
        $row = 2;
        foreach ($kpis['concentracion_clientes'] as $alias => $monto) {
            $sheet->setCellValue("A{$row}", $alias);
            $sheet->setCellValue("B{$row}", (float) $monto);
            $sheet->setCellValue("C{$row}", $total > 0 ? round((float) $monto / $total * 100, 2).'%' : '—');
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(14);

        if ($row > 2) {
            $sheet->getStyle('B2:B'.($row - 1))->getNumberFormat()->setFormatCode('"$"#,##0.00');
        }
    }

    private function styleHeader($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'B91C1C']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '1F2937']]],
        ]);
    }
}
