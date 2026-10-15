<?php

namespace App\Service;

use App\Domain\CalculadorFechaVencimiento;
use App\Domain\CalculadorNovedad;
use App\Entity\Gbl\Cliente;
use App\Entity\Gbl\Factura;
use App\Entity\Gbl\Usuario;
use App\Entity\PeriodoCierre;
use App\Entity\ReporteFactura;
use App\Repository\Gbl\ClienteRepository;
use App\Repository\Gbl\ClienteTipoRepository;
use App\Repository\Gbl\FacturaRepository;
use App\Repository\PeriodoCierreRepository;
use App\Repository\ReclasificacionRepository;
use App\Repository\ReporteFacturaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Flujo núcleo de DataCrédito: listar períodos/clientes/facturas, guardar
 * novedades y cerrar un período. Responde ya en la forma exacta que espera
 * el frontend (dataCredito.ts) — no hay una capa de serialización aparte
 * porque las filas que arma (los "candidatos") no siempre corresponden 1:1
 * a una sola entidad: mezclan gbl.factura (vivo) con reporte_factura
 * (fotografía) según cuál exista para el período pedido.
 */
class PeriodoFacturaService
{
    private ?CalculadorFechaVencimiento $calculadorFechaVencimiento = null;

    public function __construct(
        private readonly PeriodoCierreRepository $periodos,
        private readonly ClienteRepository $clientes,
        private readonly FacturaRepository $facturas,
        private readonly ReporteFacturaRepository $reportesFactura,
        private readonly ReclasificacionRepository $reclasificaciones,
        private readonly NovedadCatalogo $novedadCatalogo,
        private readonly ConfiguracionService $configuraciones,
        private readonly EmpleadoActualResolver $empleadoActual,
        private readonly EntityManagerInterface $em,
    ) {
    }

    private function calculadorFechaVencimiento(): CalculadorFechaVencimiento
    {
        return $this->calculadorFechaVencimiento ??= $this->configuraciones->calculadorFechaVencimiento();
    }

    /** @return array<int, array{id:int, fechaReporte:string, abierto:bool}> */
    public function listarPeriodos(): array
    {
        $this->asegurarPeriodoMesAnterior();

        return array_map($this->serializarPeriodo(...), $this->periodos->findAllOrdenado());
    }

    /**
     * El período del mes anterior al actual (fecha_reporte = último día de
     * ese mes) debe existir siempre en el listado, incluso si nadie lo ha
     * buscado/guardado todavía — a diferencia de obtenerOCrearPeriodo(), que
     * solo crea un período cuando alguien intenta guardar sobre él. Idempotente:
     * si ya existe (lo normal, una vez creado el primera vez que se lista),
     * obtenerOCrearPeriodo() no hace nada.
     */
    private function asegurarPeriodoMesAnterior(): void
    {
        $primerDiaMesActual = new \DateTimeImmutable('first day of this month');
        $finMesAnterior = $primerDiaMesActual->modify('-1 day');

        $this->obtenerOCrearPeriodo($finMesAnterior);
        $this->em->flush();
    }

    public function crearPeriodo(string $fechaReporteStr): array
    {
        $fechaReporte = $this->parsearFecha($fechaReporteStr, 'fechaReporte');

        if ($this->periodos->findOneByFechaReporte($fechaReporte) !== null) {
            throw new ConflictHttpException('La fecha de cierre ya existe.');
        }

        $periodo = new PeriodoCierre($fechaReporte);
        $this->em->persist($periodo);
        $this->em->flush();

        return $this->serializarPeriodo($periodo);
    }

    /** @return array<int, array<string, mixed>> ClienteReporte[] */
    public function listarClientes(string $fechaReporteStr): array
    {
        return $this->listarClientesInterno($fechaReporteStr, soloReclasificados: false);
    }

    /** @return array<int, array<string, mixed>> ClienteReporte[] — solo clientes con al menos una factura reclasificada */
    public function listarClientesReclasificados(string $fechaReporteStr): array
    {
        return $this->listarClientesInterno($fechaReporteStr, soloReclasificados: true);
    }

    /** @return array<int, array<string, mixed>> FacturaCliente[] */
    public function listarFacturasCliente(string $tercero, string $fechaReporteStr): array
    {
        return $this->listarFacturasClienteInterno($tercero, $fechaReporteStr, soloReclasificados: false);
    }

    /** @return array<int, array<string, mixed>> FacturaCliente[] — solo las reclasificadas de este cliente */
    public function listarFacturasReclasificadas(string $tercero, string $fechaReporteStr): array
    {
        return $this->listarFacturasClienteInterno($tercero, $fechaReporteStr, soloReclasificados: true);
    }

    /**
     * Todas las facturas reclasificadas de todos los clientes elegibles —
     * para el modal "Ver todas y guardar" del frontend. A diferencia de
     * listarFacturasReclasificadas(), cada fila incluye nit/cliente (no hay
     * un solo tercero en contexto).
     *
     * @return array<int, array<string, mixed>> FacturaReclasificadaResumen[]
     */
    public function listarTodasFacturasReclasificadas(string $fechaReporteStr): array
    {
        $fechaReporte = $this->parsearFecha($fechaReporteStr, 'fechaReporte');
        $periodo = $this->periodos->findOneByFechaReporte($fechaReporte);
        $calculador = $this->novedadCatalogo->calculador();

        $resultado = [];
        foreach ($this->clientes->findElegibles() as $cliente) {
            foreach ($this->candidatosParaCliente($cliente, $fechaReporte, $periodo, $calculador, soloReclasificados: true) as $candidato) {
                $resultado[] = $this->serializarFacturaCandidato($candidato) + [
                    'nit' => $cliente->getNumeroIdentidad(),
                    'cliente' => $cliente->getNombreMostrar(),
                ];
            }
        }

        return $resultado;
    }

    /** @return array{totalRegistros:int, totalNovedades:int, resumen: array<int, array{novedad:int, cantidad:int}>} */
    public function resumenNovedades(string $fechaReporteStr): array
    {
        $fechaReporte = $this->parsearFecha($fechaReporteStr, 'fechaReporte');
        $periodo = $this->periodos->findOneByFechaReporte($fechaReporte);
        $calculador = $this->novedadCatalogo->calculador();

        $conteoPorNovedad = [];
        $totalRegistros = 0;

        foreach ($this->clientes->findElegibles() as $cliente) {
            foreach ($this->candidatosParaCliente($cliente, $fechaReporte, $periodo, $calculador) as $candidato) {
                if (!$candidato['reportar']) {
                    continue;
                }

                ++$totalRegistros;
                $conteoPorNovedad[$candidato['novedadCodigo']] = ($conteoPorNovedad[$candidato['novedadCodigo']] ?? 0) + 1;
            }
        }

        ksort($conteoPorNovedad);

        $totalNovedades = 0;
        $resumen = [];
        foreach ($conteoPorNovedad as $codigo => $cantidad) {
            $totalNovedades += $codigo * $cantidad;
            $resumen[] = ['novedad' => $codigo, 'cantidad' => $cantidad];
        }

        return [
            'totalRegistros' => $totalRegistros,
            'totalNovedades' => $totalNovedades,
            'resumen' => $resumen,
        ];
    }

    /**
     * Vista previa de solo lectura de todas las facturas reportables de un
     * período (para todos los clientes elegibles) — misma construcción que
     * resumenNovedades(), pero devolviendo los candidatos completos en vez
     * de un conteo. Sin ningún persist()/flush() en su cuerpo, ni siquiera
     * si el período ya existe: es imposible por construcción que esto
     * dispare una escritura, no por una bandera condicional que alguien
     * podría desactivar por error — corrige el bug legacy #5 (simularCierre()
     * disparaba el pipeline real de guardado con solo previsualizar).
     *
     * Fuente de datos de GeneradorExcelService/GeneradorPlanoExcelService
     * cuando el período todavía está abierto (o no existe): a diferencia de
     * GeneradorTxtService, que solo lee `ReporteFactura` ya persistida (el
     * plano oficial exige un período cerrado), el Excel de vista previa
     * puede mostrar candidatos que el usuario nunca guardó explícitamente.
     *
     * @return array<int, array<string, mixed>> misma forma que candidatosParaCliente(), con 'nit'/'razonSocial' agregados
     */
    public function previsualizarFacturas(string $fechaReporteStr): array
    {
        $fechaReporte = $this->parsearFecha($fechaReporteStr, 'fechaReporte');
        $periodo = $this->periodos->findOneByFechaReporte($fechaReporte);
        $calculador = $this->novedadCatalogo->calculador();

        $resultado = [];
        foreach ($this->clientes->findElegibles() as $cliente) {
            foreach ($this->candidatosParaCliente($cliente, $fechaReporte, $periodo, $calculador) as $candidato) {
                if (!$candidato['reportar']) {
                    continue;
                }

                $resultado[] = $candidato + [
                    'nit' => $cliente->getNumeroIdentidad(),
                    'razonSocial' => $cliente->getRazonSocial(),
                ];
            }
        }

        return $resultado;
    }

    private function listarClientesInterno(string $fechaReporteStr, bool $soloReclasificados): array
    {
        $fechaReporte = $this->parsearFecha($fechaReporteStr, 'fechaReporte');
        $periodo = $this->periodos->findOneByFechaReporte($fechaReporte);
        $calculador = $this->novedadCatalogo->calculador();

        $resultado = [];
        foreach ($this->clientes->findElegibles() as $cliente) {
            $candidatos = $this->candidatosParaCliente($cliente, $fechaReporte, $periodo, $calculador, $soloReclasificados);
            if ($candidatos === []) {
                continue;
            }

            $saldoInicial = array_sum(array_map(static fn (array $c) => (float) $c['saldoAnterior'], $candidatos));
            $saldo = array_sum(array_map(static fn (array $c) => (float) $c['saldo'], $candidatos));

            $resultado[] = [
                'tercero' => $cliente->getNumeroIdentidad(),
                'nit' => $cliente->getNumeroIdentidad(),
                'cliente' => $cliente->getNombreMostrar(),
                'tipo' => $cliente->getTipoIdentificacion()?->getCodigo() ?? '',
                'saldoInicial' => $saldoInicial,
                'saldo' => $saldo,
            ];
        }

        return $resultado;
    }

    private function listarFacturasClienteInterno(string $tercero, string $fechaReporteStr, bool $soloReclasificados): array
    {
        $fechaReporte = $this->parsearFecha($fechaReporteStr, 'fechaReporte');
        $periodo = $this->periodos->findOneByFechaReporte($fechaReporte);
        $cliente = $this->clienteOFallar($tercero);
        $calculador = $this->novedadCatalogo->calculador();

        $candidatos = $this->candidatosParaCliente($cliente, $fechaReporte, $periodo, $calculador, $soloReclasificados);

        return array_map($this->serializarFacturaCandidato(...), $candidatos);
    }

    /**
     * Guarda (parcial) las ediciones de un cliente para un período — crea o
     * actualiza una fila reporte_factura por cada factura recibida. Rechaza
     * si el período está cerrado o si algún saldo enviado excede su saldo
     * anterior. En modo AUTOMATICA (flujo normal, default) la novedad
     * SIEMPRE se recalcula server-side — lo que mande el cliente en
     * `novedad` se ignora, es defensa contra un payload manipulado. En modo
     * MANUAL (flujo reclasificados, ver ReclasificacionService) la novedad
     * viene del request, validada 1-13.
     *
     * Todo el cuerpo corre dentro de una única transacción DBAL
     * (EntityManager::wrapInTransaction): guardarUnaFactura() intercala
     * persist() con lecturas por repositorio (resolverSaldoAnterior(),
     * findOneByPeriodoYFactura()), y el modo de flush por defecto de
     * Doctrine hace flush() automático antes de cada una de esas queries —
     * o sea que, sin una transacción explícita, cada factura del payload
     * quedaría confirmada en su propio commit individual. Si la factura 3
     * de 5 falla la validación de saldo, las 2 anteriores ya habrían
     * quedado guardadas en la base aunque el request completo responda
     * error. wrapInTransaction hace rollback de todo el lote ante
     * cualquier excepción (incluida BadRequestHttpException) y confirma
     * con un solo commit si todo sale bien.
     *
     * @param array<int, array<string, mixed>> $facturasPayload FacturaCliente[] tal como los envía el frontend
     * @return array{periodoId:int, fechaReporte:string, tercero:string, totalFacturas:int, creadas:int, actualizadas:int}
     */
    public function guardarNovedades(string $tercero, string $fechaReporteStr, array $facturasPayload, Usuario $usuarioActual, ModoNovedad $modo = ModoNovedad::AUTOMATICA): array
    {
        return $this->em->wrapInTransaction(function () use ($tercero, $fechaReporteStr, $facturasPayload, $usuarioActual, $modo): array {
            $periodo = $this->obtenerOCrearPeriodo($this->parsearFecha($fechaReporteStr, 'fechaReporte'));
            $this->exigirAbierto($periodo);

            $cliente = $this->clienteOFallar($tercero);
            $calculador = $this->novedadCatalogo->calculador();
            $empleadoId = $this->empleadoActual->resolver($usuarioActual);

            $creadas = 0;
            $actualizadas = 0;
            foreach ($facturasPayload as $facturaPayload) {
                if ($this->guardarUnaFactura($periodo, $cliente, $facturaPayload, $calculador, $modo, $empleadoId)) {
                    ++$creadas;
                } else {
                    ++$actualizadas;
                }
            }

            return [
                'periodoId' => $periodo->getId(),
                'fechaReporte' => $fechaReporteStr,
                'tercero' => $tercero,
                'totalFacturas' => count($facturasPayload),
                'creadas' => $creadas,
                'actualizadas' => $actualizadas,
            ];
        });
    }

    /**
     * Resuelve el período (crea uno nuevo si la fecha todavía no tiene fila)
     * para una fecha de reporte — expuesto para que ReclasificacionService
     * pueda crear la fila `Reclasificacion` en el mismo período que
     * guardarNovedades(..., ModoNovedad::MANUAL) va a usar, sin duplicar
     * esta resolución.
     */
    public function obtenerOCrearPeriodoPorFecha(string $fechaReporteStr): PeriodoCierre
    {
        return $this->obtenerOCrearPeriodo($this->parsearFecha($fechaReporteStr, 'fechaReporte'));
    }

    /** Resuelve y valida que una factura pertenezca a este tercero — expuesto para ReclasificacionService. */
    public function facturaDelCliente(string $tercero, int $facturaId): Factura
    {
        return $this->resolverFacturaDeCliente($this->clienteOFallar($tercero), $facturaId);
    }

    /**
     * Cierra un período: materializa en reporte_factura el estado actual
     * (guardado o, si nunca se tocó, calculado en vivo) de TODAS las
     * facturas elegibles — no solo las que el usuario guardó a mano — y
     * recién ahí marca abierto=false. Sin esto, una factura que el usuario
     * nunca abrió quedaría fuera del reporte cerrado.
     *
     * Todo lo que persiste corre dentro de una única transacción: son
     * potencialmente cientos de materializarCandidato() por cliente, y el
     * flush automático de Doctrine antes de cada consulta por repositorio
     * (candidatosParaCliente() del siguiente cliente) confirmaría en la
     * base cada tanda por separado si no se envuelve explícitamente. Sin
     * esto, un error a mitad de la lista de clientes elegibles dejaría el
     * cierre a medias: reporte_factura con filas de algunos clientes y el
     * período todavía marcado abierto=false nunca ejecutado, o peor, un
     * período que terminó cerrándose sin todas sus facturas materializadas.
     *
     * @return array{periodoId:int, fechaReporte:string, clientesProcesados:int, facturasMaterializadas:int, facturasYaExistentes:int, totalFacturas:int}
     */
    public function cerrarPeriodo(int $periodoId, string $fechaReporteStr, Usuario $usuarioActual): array
    {
        $periodo = $this->periodos->find($periodoId);
        if ($periodo === null) {
            throw new NotFoundHttpException('Período no encontrado.');
        }

        $fechaReporte = $this->parsearFecha($fechaReporteStr, 'fechaReporte');
        if ($periodo->getFechaReporte()->format('Y-m-d') !== $fechaReporte->format('Y-m-d')) {
            throw new BadRequestHttpException('fechaReporte no coincide con el período indicado.');
        }

        $this->exigirAbierto($periodo);

        return $this->em->wrapInTransaction(function () use ($periodo, $fechaReporte, $usuarioActual): array {
            $calculador = $this->novedadCatalogo->calculador();
            $empleadoId = $this->empleadoActual->resolver($usuarioActual);

            $clientesProcesados = 0;
            $facturasMaterializadas = 0;
            $facturasYaExistentes = 0;

            foreach ($this->clientes->findElegibles() as $cliente) {
                ++$clientesProcesados;

                // Normal + reclasificadas: las dos llamadas son un conjunto
                // complementario (candidatosParaCliente() filtra por una u
                // otra), su unión cubre todas las facturas del cliente.
                foreach ($this->candidatosParaCliente($cliente, $fechaReporte, $periodo, $calculador, false) as $candidato) {
                    $this->materializarCandidato($periodo, $candidato, $empleadoId) ? ++$facturasMaterializadas : ++$facturasYaExistentes;
                }
                foreach ($this->candidatosParaCliente($cliente, $fechaReporte, $periodo, $calculador, true) as $candidato) {
                    $this->materializarCandidato($periodo, $candidato, $empleadoId) ? ++$facturasMaterializadas : ++$facturasYaExistentes;
                }
            }

            $periodo->cerrar($empleadoId);

            return [
                'periodoId' => $periodo->getId(),
                'fechaReporte' => $fechaReporte->format('Y-m-d'),
                'clientesProcesados' => $clientesProcesados,
                'facturasMaterializadas' => $facturasMaterializadas,
                'facturasYaExistentes' => $facturasYaExistentes,
                'totalFacturas' => $facturasMaterializadas + $facturasYaExistentes,
            ];
        });
    }

    // ---------------------------------------------------------------

    /** @return bool true si se creó una fila reporte_factura nueva, false si se actualizó una existente */
    private function guardarUnaFactura(PeriodoCierre $periodo, Cliente $cliente, array $facturaPayload, CalculadorNovedad $calculador, ModoNovedad $modo, int $empleadoId): bool
    {
        $factura = $this->resolverFacturaDeCliente($cliente, (int) ($facturaPayload['facturaId'] ?? 0));

        $saldoPropuesto = (string) ($facturaPayload['saldo'] ?? '0');
        $reportar = (bool) ($facturaPayload['reportar'] ?? true);

        [$diasVencidos, $edadFacturaDias] = $this->diasVencidosYEdad($factura, $periodo->getFechaReporte());
        $saldoAnterior = $this->resolverSaldoAnterior($factura, $periodo);

        if ((float) $saldoPropuesto > (float) $saldoAnterior) {
            throw new BadRequestHttpException(sprintf(
                'El saldo de la factura %s no puede superar el saldo anterior (%s).',
                $factura->getNumeroComprobante(),
                $saldoAnterior,
            ));
        }

        if ($modo === ModoNovedad::MANUAL) {
            $novedadCodigo = (int) ($facturaPayload['novedad'] ?? 0);
            if ($novedadCodigo < 1 || $novedadCodigo > 13) {
                throw new BadRequestHttpException('El campo "novedad" debe estar entre 1 y 13.');
            }
            $saldoResuelto = $saldoPropuesto;
        } else {
            ['saldo' => $saldoResuelto, 'novedadCodigo' => $novedadCodigo] = $this->resolverSaldoYNovedad(
                $calculador,
                $saldoPropuesto,
                $diasVencidos,
                $edadFacturaDias,
                $cliente->getCobroJuridico() ?? 0,
            );
        }

        $novedad = $this->novedadCatalogo->porCodigo($novedadCodigo);
        $existente = $this->reportesFactura->findOneByPeriodoYFactura($periodo, $factura);

        if ($existente === null) {
            $existente = new ReporteFactura($periodo, $factura, $cliente, $saldoAnterior, $saldoResuelto, $novedad, $diasVencidos, $reportar, $empleadoId);
            $this->em->persist($existente);

            return true;
        }

        $existente->actualizar($saldoResuelto, $novedad, $diasVencidos, $reportar, $empleadoId);

        return false;
    }

    private function resolverFacturaDeCliente(Cliente $cliente, int $facturaId): Factura
    {
        $factura = $this->facturas->find($facturaId);
        if ($factura === null || $factura->getCliente()->getId() !== $cliente->getId()) {
            throw new NotFoundHttpException(sprintf('Factura %d no encontrada para este cliente.', $facturaId));
        }

        return $factura;
    }

    /** @return bool true si se materializó una fila reporte_factura nueva, false si ya existía de un guardado parcial previo */
    private function materializarCandidato(PeriodoCierre $periodo, array $candidato, int $empleadoId): bool
    {
        if ($candidato['reporteExistente'] !== null) {
            return false; // ya materializado por un guardado parcial previo
        }

        $novedad = $this->novedadCatalogo->porCodigo($candidato['novedadCodigo']);
        $fila = new ReporteFactura(
            $periodo,
            $candidato['factura'],
            $candidato['cliente'],
            $candidato['saldoAnterior'],
            $candidato['saldo'],
            $novedad,
            $candidato['diasVencidos'],
            $candidato['reportar'],
            $empleadoId,
        );
        $this->em->persist($fila);

        return true;
    }

    /**
     * Todas las facturas elegibles de un cliente, resueltas contra el
     * período pedido — la fuente única para listarClientes/listarFacturas/
     * resumen/cierre, en sus dos variantes (flujo normal y reclasificados).
     * $periodo puede ser null (fecha sin período creado todavía): se trata
     * como "período virtual, abierto, sin nada guardado".
     *
     * $soloReclasificados = false (flujo normal): excluye cualquier factura
     * reclasificada desde este período o antes. true (flujo reclasificados):
     * incluye únicamente esas — las dos llamadas son complementarias, nunca
     * se solapan, ver cerrarPeriodo().
     *
     * @return array<int, array<string, mixed>>
     */
    private function candidatosParaCliente(Cliente $cliente, \DateTimeImmutable $fechaReporte, ?PeriodoCierre $periodo, CalculadorNovedad $calculador, bool $soloReclasificados = false): array
    {
        $existentesPorFacturaId = $periodo !== null ? $this->reportesFactura->mapaPorFacturaId($periodo) : [];
        $reclasificadasPorFacturaId = $this->reclasificaciones->mapaPorFacturaId($cliente, $fechaReporte);

        $candidatos = [];
        foreach ($this->facturas->findByCliente($cliente) as $factura) {
            $estaReclasificada = isset($reclasificadasPorFacturaId[$factura->getId()]);
            if ($estaReclasificada !== $soloReclasificados) {
                continue;
            }

            $existente = $existentesPorFacturaId[$factura->getId()] ?? null;

            if ($existente !== null) {
                [, $edadFacturaDiasExistente] = $this->diasVencidosYEdad($factura, $fechaReporte);
                $candidatos[] = [
                    'factura' => $factura,
                    'cliente' => $cliente,
                    'reporteExistente' => $existente,
                    'saldoAnterior' => $existente->getSaldoInicial(),
                    'saldo' => $existente->getSaldo(),
                    'novedadCodigo' => $existente->getNovedad()->getCodigo(),
                    'diasVencidos' => $existente->getDiasVencidos(),
                    'dias' => $edadFacturaDiasExistente,
                    'reportar' => $existente->isReportar(),
                ];
                continue;
            }

            [$diasVencidos, $edadFacturaDias] = $this->diasVencidosYEdad($factura, $fechaReporte);
            $saldoAnterior = $this->resolverSaldoAnterior($factura, $periodo, $fechaReporte);

            ['saldo' => $saldo, 'novedadCodigo' => $novedadCodigo] = $this->resolverSaldoYNovedad(
                $calculador,
                $saldoAnterior,
                $diasVencidos,
                $edadFacturaDias,
                $cliente->getCobroJuridico() ?? 0,
            );

            $candidatos[] = [
                'factura' => $factura,
                'cliente' => $cliente,
                'reporteExistente' => null,
                'saldoAnterior' => $saldoAnterior,
                'saldo' => $saldo,
                'novedadCodigo' => $novedadCodigo,
                'diasVencidos' => $diasVencidos,
                'dias' => $edadFacturaDias,
                'reportar' => true,
            ];
        }

        return $candidatos;
    }

    /**
     * "Saldo anterior" = el punto de partida del período: la fotografía del
     * período CERRADO más reciente para esta factura, o si nunca se ha
     * reportado, el saldo en vivo de NetSuite (gbl.factura.saldo_actual).
     * Nunca el saldo editable de un período todavía abierto — ese es "saldo".
     */
    private function resolverSaldoAnterior(Factura $factura, ?PeriodoCierre $periodoActual, ?\DateTimeImmutable $fechaReporte = null): string
    {
        $fechaCorte = $fechaReporte ?? $periodoActual?->getFechaReporte();
        $anterior = $fechaCorte !== null ? $this->reportesFactura->findUltimoCerradoParaFactura($factura, $fechaCorte) : null;

        return $anterior?->getSaldo() ?? $factura->getSaldoActual();
    }

    /** @return array{0:int, 1:int} [diasVencidos, edadFacturaDias] */
    private function diasVencidosYEdad(Factura $factura, \DateTimeImmutable $fechaReporte): array
    {
        $plazoPagoDias = $factura->getPlazoPagoDias() ?? 0;
        $fechaVencimiento = $this->calculadorFechaVencimiento()->calcular($factura->getFechaEmision(), $plazoPagoDias);

        $diasVencidos = max(0, (int) floor(($fechaReporte->getTimestamp() - $fechaVencimiento->getTimestamp()) / 86400));
        $edadFacturaDias = max(0, (int) floor(($fechaReporte->getTimestamp() - $factura->getFechaEmision()->getTimestamp()) / 86400));

        return [$diasVencidos, $edadFacturaDias];
    }

    /**
     * Aplica el castigo por materialidad/edad: si el umbral (10000) o la
     * edad máxima (2920 días) disparan la novedad 5, el saldo efectivo se
     * fuerza a 0 — no solo la novedad. Un mismo punto para las dos rutas
     * (lectura en vivo y guardado) evita que diverjan.
     *
     * @return array{saldo: string, novedadCodigo: int}
     */
    private function resolverSaldoYNovedad(CalculadorNovedad $calculador, string $saldoPropuesto, int $diasVencidos, int $edadFacturaDias, int $cobroJuridico): array
    {
        $saldoFloat = (float) $saldoPropuesto;
        $novedadCodigo = $calculador->calcular($saldoFloat, $diasVencidos, $edadFacturaDias, $cobroJuridico);

        $saldoResuelto = $novedadCodigo === 5 && $calculador->esCastigoPorMaterialidadOEdad($saldoFloat, $edadFacturaDias)
            ? '0.00'
            : $saldoPropuesto;

        return ['saldo' => $saldoResuelto, 'novedadCodigo' => $novedadCodigo];
    }

    private function obtenerOCrearPeriodo(\DateTimeImmutable $fechaReporte): PeriodoCierre
    {
        $periodo = $this->periodos->findOneByFechaReporte($fechaReporte);
        if ($periodo !== null) {
            return $periodo;
        }

        $periodo = new PeriodoCierre($fechaReporte);
        $this->em->persist($periodo);

        return $periodo;
    }

    private function exigirAbierto(PeriodoCierre $periodo): void
    {
        if (!$periodo->isAbierto()) {
            throw new ConflictHttpException('El período de reporte se encuentra cerrado.');
        }
    }

    /**
     * Resuelve un tercero y exige que esté en el universo reportable
     * (mismo gate que findElegibles() — autoriza_reporte + nacional). Un
     * cliente que existe pero no autoriza reportar se trata como "no
     * encontrado", no como un 403 aparte: es invisible en listarClientes()
     * por la misma razón, y este es el único punto de entrada por NIT
     * (listarFacturasCliente/guardarNovedades) — sin este chequeo aquí, se
     * podía consultar/editar facturas de un cliente sin autorización
     * llamando directo al endpoint con su NIT, evadiendo el filtro que
     * listarClientes() sí aplica.
     */
    private function clienteOFallar(string $tercero): Cliente
    {
        $cliente = $this->clientes->findOneByNumeroIdentidad($tercero);
        if ($cliente === null || !$cliente->isAutorizaReporte() || $cliente->getTipoCliente()?->getDescripcion() !== ClienteTipoRepository::NACIONAL) {
            throw new NotFoundHttpException(sprintf('Cliente "%s" no encontrado.', $tercero));
        }

        return $cliente;
    }

    private function parsearFecha(string $fecha, string $campo): \DateTimeImmutable
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        if ($parsed === false) {
            throw new BadRequestHttpException(sprintf('El campo "%s" debe tener formato YYYY-MM-DD.', $campo));
        }

        return $parsed;
    }

    private function serializarPeriodo(PeriodoCierre $periodo): array
    {
        return [
            'id' => $periodo->getId(),
            'fechaReporte' => $periodo->getFechaReporte()->format('Y-m-d'),
            'abierto' => $periodo->isAbierto(),
        ];
    }

    private function serializarFacturaCandidato(array $candidato): array
    {
        /** @var Factura $factura */
        $factura = $candidato['factura'];
        $plazoPagoDias = $factura->getPlazoPagoDias() ?? 0;
        $fechaVencimiento = $this->calculadorFechaVencimiento()->calcular($factura->getFechaEmision(), $plazoPagoDias);
        $reporteExistente = $candidato['reporteExistente'];

        return [
            'facturaId' => $factura->getId(),
            'clienteId' => $candidato['cliente']->getId(),
            'datacreditoReporteCierreId' => $reporteExistente?->getId() ?? 0,
            'factura' => $factura->getNumeroComprobante(),
            'fechaEmision' => $factura->getFechaEmision()->format('Y-m-d'),
            'fechaVencimiento' => $fechaVencimiento->format('Y-m-d'),
            'valor' => (float) $factura->getTotal(),
            'saldoAnterior' => (float) $candidato['saldoAnterior'],
            'saldo' => (float) $candidato['saldo'],
            'novedad' => $candidato['novedadCodigo'],
            'reportar' => $candidato['reportar'] ? 1 : 0,
            'diasVencidos' => $candidato['diasVencidos'],
            'dias' => $candidato['dias'],
        ];
    }
}
