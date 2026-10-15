<?php

namespace App\Service\Reporte;

use App\Entity\PeriodoCierre;
use App\Entity\ReporteFactura;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Excel "plano": reproduce fila por fila la misma secuencia cabecera/
 * cuerpo/cola del TXT (ver GeneradorTxtService), no es una vista de
 * conveniencia para un revisor humano — este archivo se sube tal cual al
 * sistema externo de centrales de riesgo, que exige la misma estructura
 * que ya produce heos4: una fila de encabezados con los nombres de los
 * campos de la CABECERA (columnas A-K; el resto quedan vacías porque el
 * legacy nunca las rotuló), luego la cabecera como su propia fila (misma
 * columnas A-K), luego una fila por factura con los 69 campos del cuerpo
 * (columnas A-BQ, ver GeneradorTxtService::camposDeFila()), y por último
 * la cola en sus propias 5 columnas (A-E) — confirmado comparando
 * campo a campo contra un Excel real generado por heos4 (no es una
 * suposición: cada bloque ocupa exactamente las columnas que ese archivo
 * de referencia usa, aunque no coincidan con las de los otros bloques).
 *
 * Mismo criterio que GeneradorTxtService: solo períodos cerrados,
 * `ReporteFactura` ya persistida — estrictamente de solo lectura.
 */
final class GeneradorPlanoExcelService
{
    public function __construct(
        private readonly GeneradorTxtService $generadorTxt,
    ) {
    }

    /** @param ReporteFactura[] $reportesFactura */
    public function generar(PeriodoCierre $periodo, array $reportesFactura): string
    {
        $filas = array_values(array_filter(
            $reportesFactura,
            static fn (ReporteFactura $rf): bool => $rf->isReportar(),
        ));
        $fechaReporte = $periodo->getFechaReporte();

        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle('Plano Excel');

        $camposCabecera = $this->generadorTxt->camposCabecera($fechaReporte);
        $hoja->fromArray(array_keys($camposCabecera), null, 'A1');

        $numeroFila = 2;
        $hoja->fromArray(array_values($camposCabecera), null, 'A'.$numeroFila);
        ++$numeroFila;

        $sumatoriaNovedades = 0;
        foreach ($filas as $rf) {
            $campos = $this->generadorTxt->camposDeFila($rf, $fechaReporte);
            $hoja->fromArray(array_values($campos), null, 'A'.$numeroFila);
            $sumatoriaNovedades += $rf->getNovedad()->getCodigo();
            ++$numeroFila;
        }

        $camposCola = $this->generadorTxt->camposCola($fechaReporte, count($filas) + 2, $sumatoriaNovedades);
        $hoja->fromArray(array_values($camposCola), null, 'A'.$numeroFila);

        // El cuerpo (69 columnas) siempre es el bloque más ancho de los tres
        // — se autoajustan todas para que ninguna fila quede recortada.
        foreach (range(1, 69) as $indice) {
            $hoja->getColumnDimensionByColumn($indice)->setAutoSize(true);
        }

        return $this->aBinario($spreadsheet);
    }

    private function aBinario(Spreadsheet $spreadsheet): string
    {
        $archivoTemporal = tempnam(sys_get_temp_dir(), 'datacredito_plano_excel_');
        try {
            (new Xlsx($spreadsheet))->save($archivoTemporal);

            return file_get_contents($archivoTemporal);
        } finally {
            unlink($archivoTemporal);
        }
    }
}
