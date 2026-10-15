<?php

namespace App\Service;

use App\Entity\Gbl\Usuario;
use App\Repository\Gbl\FacturaRepository;
use App\Repository\ReclasificacionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Envuelve PeriodoFacturaService::guardarNovedades(..., ModoNovedad::MANUAL)
 * y además crea (si no existía) la fila de auditoría Reclasificacion por
 * cada factura — composición, no duplicación de la lógica de guardado en sí
 * (ver Fase 3 del plan: por qué esto es un wrapper delgado y no una
 * jerarquía Strategy separada).
 */
class ReclasificacionService
{
    public function __construct(
        private readonly PeriodoFacturaService $periodoFacturas,
        private readonly ReclasificacionRepository $reclasificaciones,
        private readonly FacturaRepository $facturas,
        private readonly EmpleadoActualResolver $empleadoActual,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * Envuelto en una transacción propia: crea la fila Reclasificacion por
     * cada factura Y guarda las novedades (que abre su propia transacción
     * anidada, ver PeriodoFacturaService::guardarNovedades()) como una sola
     * unidad — si guardarNovedades() rechaza una factura (saldo inválido,
     * novedad fuera de rango, período cerrado), las Reclasificacion ya
     * creadas para las facturas anteriores del mismo payload también se
     * revierten, en vez de quedar huérfanas sin su novedad guardada.
     *
     * @param array<int, array<string, mixed>> $facturasPayload FacturaCliente[]
     * @return array{tercero:string, motivo:?string, reclasificacionesCreadas:int, novedades:array{periodoId:int, fechaReporte:string, tercero:string, totalFacturas:int, creadas:int, actualizadas:int}}
     */
    public function guardar(string $tercero, string $fechaReporteStr, array $facturasPayload, Usuario $usuarioActual, ?string $motivo = null): array
    {
        return $this->em->wrapInTransaction(function () use ($tercero, $fechaReporteStr, $facturasPayload, $usuarioActual, $motivo): array {
            $periodo = $this->periodoFacturas->obtenerOCrearPeriodoPorFecha($fechaReporteStr);
            $empleadoId = $this->empleadoActual->resolver($usuarioActual);

            $reclasificacionesCreadas = 0;
            foreach ($facturasPayload as $facturaPayload) {
                $factura = $this->periodoFacturas->facturaDelCliente($tercero, (int) ($facturaPayload['facturaId'] ?? 0));
                if ($this->reclasificaciones->crearSiNoExiste($factura, $periodo, $empleadoId, $motivo)['creada']) {
                    ++$reclasificacionesCreadas;
                }
            }

            $novedades = $this->periodoFacturas->guardarNovedades($tercero, $fechaReporteStr, $facturasPayload, $usuarioActual, ModoNovedad::MANUAL);

            return [
                'tercero' => $tercero,
                'motivo' => $motivo,
                'reclasificacionesCreadas' => $reclasificacionesCreadas,
                'novedades' => $novedades,
            ];
        });
    }

    /**
     * Variante bulk del modal "Ver todas y guardar": las facturas pueden
     * pertenecer a distintos clientes en un solo payload. Se agrupan por el
     * tercero de cada una y se delega a guardar() por grupo — mismo camino,
     * sin duplicar la lógica de guardado.
     *
     * Todo el lote (todos los terceros) corre en una única transacción
     * envolvente: guardar() por tercero abre su propia transacción anidada,
     * pero antes esto significaba que cada tercero se confirmaba en la base
     * por separado — si el tercero 3 de 5 fallaba, los terceros 1 y 2
     * quedaban guardados aunque el request completo respondiera error. Con
     * wrapInTransaction acá afuera, un fallo en cualquier tercero revierte
     * el lote completo: "guardar todas" es ahora todo-o-nada.
     *
     * @param array<int, array<string, mixed>> $facturasPayload FacturaCliente[]
     * @return array{fechaReporte:string, totalTerceros:int, totalFacturas:int, reclasificacionesCreadas:int, porTercero: array<int, array{tercero:string, motivo:?string, reclasificacionesCreadas:int, novedades:array{periodoId:int, fechaReporte:string, tercero:string, totalFacturas:int, creadas:int, actualizadas:int}}>}
     */
    public function guardarTodas(string $fechaReporteStr, array $facturasPayload, Usuario $usuarioActual, ?string $motivo = null): array
    {
        $porTercero = [];
        foreach ($facturasPayload as $facturaPayload) {
            $facturaId = (int) ($facturaPayload['facturaId'] ?? 0);
            $factura = $this->facturas->find($facturaId);
            if ($factura === null) {
                throw new NotFoundHttpException(sprintf('Factura %d no encontrada.', $facturaId));
            }

            $tercero = $factura->getCliente()->getNumeroIdentidad();
            $porTercero[$tercero][] = $facturaPayload;
        }

        return $this->em->wrapInTransaction(function () use ($porTercero, $fechaReporteStr, $usuarioActual, $motivo): array {
            $resultadosPorTercero = [];
            foreach ($porTercero as $tercero => $facturasDelTercero) {
                $resultadosPorTercero[] = $this->guardar($tercero, $fechaReporteStr, $facturasDelTercero, $usuarioActual, $motivo);
            }

            return [
                'fechaReporte' => $fechaReporteStr,
                'totalTerceros' => count($resultadosPorTercero),
                'totalFacturas' => array_sum(array_map(static fn (array $r) => $r['novedades']['totalFacturas'], $resultadosPorTercero)),
                'reclasificacionesCreadas' => array_sum(array_map(static fn (array $r) => $r['reclasificacionesCreadas'], $resultadosPorTercero)),
                'porTercero' => $resultadosPorTercero,
            ];
        });
    }
}
