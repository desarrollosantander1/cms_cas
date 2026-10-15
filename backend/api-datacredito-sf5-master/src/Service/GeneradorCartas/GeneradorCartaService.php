<?php

namespace App\Service\GeneradorCartas;

use App\Domain\CalculadorFechaVencimiento;
use App\Entity\Gbl\Cliente;
use App\Entity\Gbl\Factura;
use App\Repository\Gbl\ClienteRepository;
use App\Repository\Gbl\FacturaRepository;
use App\Service\ConfiguracionService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Fachada del Generador de Cartas: búsqueda de clientes, listado de facturas
 * con saldo y generación del PDF. Orquesta ClienteRepository/FacturaRepository
 * + ArmadorCartaService + CartaPdfService; los controladores quedan finos.
 */
final class GeneradorCartaService
{
    private ?CalculadorFechaVencimiento $calculadorFechaVencimiento = null;

    public function __construct(
        private readonly ClienteRepository $clientes,
        private readonly FacturaRepository $facturas,
        private readonly ConfiguracionService $configuraciones,
        private readonly ArmadorCartaService $armador,
        private readonly CartaPdfService $pdf,
        private readonly CartaMailerService $mailer,
    ) {
    }

    private function calculadorFechaVencimiento(): CalculadorFechaVencimiento
    {
        return $this->calculadorFechaVencimiento ??= $this->configuraciones->calculadorFechaVencimiento();
    }

    /** @return array<int, array<string, mixed>> */
    public function buscarClientes(string $q): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return [];
        }

        return array_map($this->serializarCliente(...), $this->clientes->buscar($q));
    }

    /**
     * Facturas con saldo > 0 del NIT, ya con fecha de vencimiento y días de
     * mora calculados — el frontend las muestra en la tabla con checkbox.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarFacturasConSaldo(string $nit): array
    {
        $this->clienteOFallar($nit);

        $hoy = new \DateTimeImmutable('today');

        return array_map(
            fn (Factura $f): array => $this->serializarFactura($f, $hoy),
            $this->facturas->buscarPorNitConSaldo($nit),
        );
    }

    /**
     * @param string[]  $comprobantesSeleccionados p.ej. ["FE-1023-1", "FE-1044-1"]
     *
     * @return string bytes del PDF
     */
    public function generarPdf(
        string $tipoValor,
        string $nit,
        array $comprobantesSeleccionados,
        ?int $paramsDays,
        string $emailAsesor,
        ?float $tasaMensualOverride = null,
    ): string {
        [, $pdfBytes] = $this->construirPdf($tipoValor, $nit, $comprobantesSeleccionados, $paramsDays, $emailAsesor, $tasaMensualOverride);

        return $pdfBytes;
    }

    /**
     * Regenera la carta desde los mismos parámetros que /pdf y la envía por
     * correo con el PDF adjunto — no se sube el PDF desde el frontend (a
     * diferencia del legacy, que hacía ese round-trip).
     *
     * @param string[] $comprobantesSeleccionados
     */
    public function enviarPorCorreo(
        string $tipoValor,
        string $nit,
        array $comprobantesSeleccionados,
        ?int $paramsDays,
        string $emailAsesor,
        string $destinatario,
        ?float $tasaMensualOverride = null,
    ): void {
        [$tipo, $pdfBytes] = $this->construirPdf($tipoValor, $nit, $comprobantesSeleccionados, $paramsDays, $emailAsesor, $tasaMensualOverride);

        $this->mailer->enviar($tipo, $pdfBytes, $destinatario, $emailAsesor ?: null);
    }

    public function nombreArchivo(string $tipoValor): string
    {
        return TipoCartaJuridica::desde($tipoValor)->nombreArchivo();
    }

    /**
     * @param string[] $comprobantesSeleccionados
     *
     * @return array{0: TipoCartaJuridica, 1: string}
     */
    private function construirPdf(
        string $tipoValor,
        string $nit,
        array $comprobantesSeleccionados,
        ?int $paramsDays,
        string $emailAsesor,
        ?float $tasaMensualOverride = null,
    ): array {
        $tipo = TipoCartaJuridica::desde($tipoValor);
        $cliente = $this->clienteOFallar($nit);

        $seleccion = array_values(array_unique(array_filter(array_map('strval', $comprobantesSeleccionados))));
        if ($seleccion === []) {
            throw new BadRequestHttpException('Debe seleccionar al menos una factura.');
        }

        $porComprobante = [];
        foreach ($this->facturas->buscarPorNitConSaldo($nit) as $factura) {
            $porComprobante[$factura->getComprobante().'-'.$factura->getNumero()] = $factura;
        }

        $facturas = [];
        foreach ($seleccion as $comprobante) {
            if (!isset($porComprobante[$comprobante])) {
                throw new BadRequestHttpException(sprintf('La factura "%s" no existe o no tiene saldo pendiente.', $comprobante));
            }
            $facturas[] = $porComprobante[$comprobante];
        }

        if ($paramsDays !== null && $paramsDays < 0) {
            throw new BadRequestHttpException('El parámetro "paramsDays" no puede ser negativo.');
        }

        $contexto = $this->armador->armarContexto($tipo, $cliente, $facturas, $paramsDays, $emailAsesor, $tasaMensualOverride);

        return [$tipo, $this->pdf->generar($tipo, $contexto)];
    }

    private function clienteOFallar(string $nit): Cliente
    {
        $cliente = $this->clientes->findOneByNumeroIdentidad($nit);
        if ($cliente === null) {
            throw new NotFoundHttpException(sprintf('Cliente con NIT "%s" no encontrado.', $nit));
        }

        return $cliente;
    }

    /** @return array<string, mixed> */
    private function serializarCliente(Cliente $cliente): array
    {
        return [
            'nit' => $cliente->getNumeroIdentidad(),
            'nombre' => $cliente->getNombreMostrar(),
        ];
    }

    /** @return array<string, mixed> */
    private function serializarFactura(Factura $factura, \DateTimeImmutable $hoy): array
    {
        $plazoPagoDias = $factura->getPlazoPagoDias() ?? 0;
        $fechaVencimiento = $this->calculadorFechaVencimiento()->calcular($factura->getFechaEmision(), $plazoPagoDias);
        $diasMora = max(0, (int) floor(($hoy->getTimestamp() - $fechaVencimiento->getTimestamp()) / 86400));

        return [
            'comprobante' => $factura->getComprobante().'-'.$factura->getNumero(),
            'fechaFactura' => $factura->getFechaEmision()->format('Y-m-d'),
            'fechaVencimiento' => $fechaVencimiento->format('Y-m-d'),
            'valorInicial' => (float) $factura->getTotal(),
            'saldo' => (float) $factura->getSaldoActual(),
            'diasMora' => $diasMora,
            // Correo de la sucursal de la factura — el frontend prellena con
            // esto el campo "Enviar a" del diálogo de correo (editable).
            'correo' => $factura->getClienteSucursal()->getCorreo(),
        ];
    }
}
