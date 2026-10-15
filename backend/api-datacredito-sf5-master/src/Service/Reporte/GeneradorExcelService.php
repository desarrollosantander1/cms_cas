<?php

namespace App\Service\Reporte;

use App\Domain\CalculadorFechaVencimiento;
use App\Entity\Gbl\Cliente;
use App\Entity\Gbl\Factura;
use App\Service\ConfiguracionService;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Genera el Excel de negocio (vista simplificada, 10 columnas) que porta
 * el bloque `else` de DataCreditoService::obtenerReporteDatacredito() del
 * legacy. A diferencia de GeneradorTxtService (solo períodos cerrados,
 * `ReporteFactura` ya persistida), este consume el shape "candidato" de
 * `PeriodoFacturaService::previsualizarFacturas()` — el legacy permite este
 * Excel tanto para períodos abiertos como cerrados (`ABIERTO IN (1, 0)`),
 * a diferencia del TXT oficial que exige cerrado.
 *
 * Estrictamente de solo lectura: recibe candidatos ya calculados, nunca
 * llama a EntityManager ni a PeriodoFacturaService::guardarNovedades().
 */
final class GeneradorExcelService
{
    private ?CalculadorFechaVencimiento $calculadorFechaVencimiento = null;

    public function __construct(
        private readonly ConfiguracionService $configuraciones,
    ) {
    }

    private function calculadorFechaVencimiento(): CalculadorFechaVencimiento
    {
        return $this->calculadorFechaVencimiento ??= $this->configuraciones->calculadorFechaVencimiento();
    }

    /** @param array<int, array<string, mixed>> $candidatos shape de PeriodoFacturaService::previsualizarFacturas() */
    public function generar(array $candidatos): string
    {
        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle('DataCrédito');

        $encabezados = ['TIPO', 'IDE', 'RAZON_SOCIAL', 'FACTURA', 'FECHA_EMISION', 'FECHA_VENCIMIENTO', 'VALOR_INICIAL', 'SALDO_DOC', 'NOVEDAD', 'CADUCO'];
        $hoja->fromArray($encabezados, null, 'A1');

        $fila = 2;
        foreach ($candidatos as $candidato) {
            /** @var Factura $factura */
            $factura = $candidato['factura'];
            /** @var Cliente $cliente */
            $cliente = $candidato['cliente'];
            $novedad = (int) $candidato['novedadCodigo'];
            $fechaVencimiento = $this->calculadorFechaVencimiento()->calcular($factura->getFechaEmision(), $factura->getPlazoPagoDias() ?? 0);

            $hoja->fromArray([
                $cliente->getTipoIdentificacion()?->getCodigo() ?? 'N',
                $cliente->getNumeroIdentidad(),
                $cliente->getNombreMostrar(),
                $factura->getNumeroComprobante(),
                $this->fechaConHoraFija($factura->getFechaEmision()),
                $this->fechaConHoraFija($fechaVencimiento),
                round((float) $factura->getTotal()), // VALOR_INICIAL ≈ CUPO_APROBADO — ver GeneradorTxtService (cc2.TOTAL no existe en este modelo)
                $novedad === 5 ? 0 : round((float) $candidato['saldo']), // SALDO_DOC ≈ CUPO_UTILIZADO
                $novedad,
                $this->fechaConHoraFija($factura->getFechaEmision()->modify(sprintf('+%d days', $this->configuraciones->novedadEdadMaximaDias()))), // CADUCO
            ], null, 'A'.$fila);
            ++$fila;
        }

        foreach (['E', 'F', 'J'] as $columna) {
            $hoja->getStyle($columna.'2:'.$columna.($fila - 1))->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        }
        foreach (range('A', 'J') as $columna) {
            $hoja->getColumnDimension($columna)->setAutoSize(true);
        }

        return $this->aBinario($spreadsheet);
    }

    /**
     * Fecha de calendario (sin hora propia) exportada como valor de fecha
     * real de Excel — no texto plano — para que el formato dd/mm/yyyy de la
     * columna la muestre como fecha ordenable. La hora se fija a 23:59:28
     * (fin del día) porque el sistema aguas abajo que consume este Excel
     * espera un datetime completo por cada fecha, aunque en pantalla solo
     * se vea el día.
     */
    private function fechaConHoraFija(\DateTimeImmutable $fecha): float
    {
        return Date::dateTimeToExcel($fecha->setTime(23, 59, 28));
    }

    private function aBinario(Spreadsheet $spreadsheet): string
    {
        $archivoTemporal = tempnam(sys_get_temp_dir(), 'datacredito_excel_');
        try {
            (new Xlsx($spreadsheet))->save($archivoTemporal);

            return file_get_contents($archivoTemporal);
        } finally {
            unlink($archivoTemporal);
        }
    }
}
