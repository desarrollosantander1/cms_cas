<?php

namespace App\Controller;

use App\Entity\Gbl\Usuario;
use App\Entity\PeriodoCierre;
use App\Entity\PlanoGenerado;
use App\Entity\ReporteFactura;
use App\Repository\PeriodoCierreRepository;
use App\Service\EmpleadoActualResolver;
use App\Service\PeriodoFacturaService;
use App\Service\Reporte\GeneradorExcelService;
use App\Service\Reporte\GeneradorPlanoExcelService;
use App\Service\Reporte\GeneradorTxtService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Genera y entrega los archivos de reporte (TXT/Excel/plano-Excel). Todos
 * los generadores (`GeneradorTxtService`/`GeneradorExcelService`/
 * `GeneradorPlanoExcelService`) son estrictamente de solo lectura contra
 * datos ya calculados — este controlador nunca llama a
 * `PeriodoFacturaService::guardarNovedades()`/`cerrarPeriodo()`, ni pasa
 * por ninguna ruta que pudiera hacerlo. Corrige el bug legacy #5 (preview
 * con efectos secundarios) por construcción, no por una bandera.
 *
 * TXT y plano-Excel exigen período CERRADO (son la vista del plano oficial
 * — `ReporteFactura` ya persistida y congelada); Excel (vista de negocio
 * simplificada) acepta también un período abierto, vía
 * `PeriodoFacturaService::previsualizarFacturas()` — mismo criterio que el
 * legacy (`ABIERTO IN (1, 0)` solo para el Excel, `IN (0)` para el TXT).
 */
class ReporteController extends AbstractController
{
    public function __construct(
        private readonly PeriodoCierreRepository $periodos,
        private readonly PeriodoFacturaService $periodoFacturas,
        private readonly GeneradorTxtService $generadorTxt,
        private readonly GeneradorExcelService $generadorExcel,
        private readonly GeneradorPlanoExcelService $generadorPlanoExcel,
        private readonly EmpleadoActualResolver $empleadoActual,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/reportes/txt', name: 'datacredito_reporte_txt', methods: ['POST'])]
    #[IsGranted('ROLE_DATACREDITO_CERRAR')]
    public function txt(Request $request): Response
    {
        $periodo = $this->periodoCerradoDeRequest($request);
        $filas = $this->reportesFacturaDelPeriodo($periodo);

        $contenido = $this->generadorTxt->generar($periodo, $filas);
        $nombreArchivo = $this->generadorTxt->nombreArchivo($periodo->getFechaReporte());

        $this->registrarAuditoria($periodo, PlanoGenerado::TIPO_TXT, $contenido, $nombreArchivo, count($filas));

        return $this->archivo($contenido, $nombreArchivo, 'text/plain; charset=UTF-8');
    }

    #[Route('/reportes/excel', name: 'datacredito_reporte_excel', methods: ['POST'])]
    #[IsGranted('ROLE_DATACREDITO_VER')]
    public function excel(Request $request): Response
    {
        $fechaReporte = $this->fechaReporteDeRequest($request);
        $periodo = $this->periodos->findOneByFechaReporte(new \DateTimeImmutable($fechaReporte));

        $candidatos = $this->periodoFacturas->previsualizarFacturas($fechaReporte);
        $contenido = $this->generadorExcel->generar($candidatos);
        $nombreArchivo = sprintf('datacredito_%s.xlsx', str_replace('-', '', $fechaReporte));

        // Sin auditoría si el período todavía no existe como fila propia
        // (fecha "virtual", nada guardado aún — ver el docblock de
        // PeriodoFacturaService::candidatosParaCliente()): PlanoGenerado
        // exige un PeriodoCierre real, y no hay nada oficial que auditar
        // todavía, es una vista previa efímera. TXT/plano-Excel siempre
        // tienen período (lo exige periodoCerradoDeRequest()), así que
        // esto solo aplica a esta ruta.
        if ($periodo !== null) {
            $this->registrarAuditoria($periodo, PlanoGenerado::TIPO_EXCEL, $contenido, $nombreArchivo, count($candidatos));
        }

        return $this->archivo($contenido, $nombreArchivo, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    #[Route('/reportes/plano-excel', name: 'datacredito_reporte_plano_excel', methods: ['POST'])]
    #[IsGranted('ROLE_DATACREDITO_CERRAR')]
    public function planoExcel(Request $request): Response
    {
        $periodo = $this->periodoCerradoDeRequest($request);
        $filas = $this->reportesFacturaDelPeriodo($periodo);

        $contenido = $this->generadorPlanoExcel->generar($periodo, $filas);
        $nombreArchivo = sprintf('datacredito_plano_%s.xlsx', $periodo->getFechaReporte()->format('Ymd'));

        $this->registrarAuditoria($periodo, PlanoGenerado::TIPO_PLANO_EXCEL, $contenido, $nombreArchivo, count($filas));

        return $this->archivo($contenido, $nombreArchivo, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    // ---------------------------------------------------------------

    private function fechaReporteDeRequest(Request $request): string
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload) || !isset($payload['fechaReporte']) || trim((string) $payload['fechaReporte']) === '') {
            throw new BadRequestHttpException('El campo "fechaReporte" es requerido.');
        }

        return (string) $payload['fechaReporte'];
    }

    private function periodoCerradoDeRequest(Request $request): PeriodoCierre
    {
        $fechaReporte = $this->fechaReporteDeRequest($request);
        $periodo = $this->periodos->findOneByFechaReporte(new \DateTimeImmutable($fechaReporte));

        if ($periodo === null) {
            throw new NotFoundHttpException('Período no encontrado.');
        }

        if ($periodo->isAbierto()) {
            throw new ConflictHttpException('El período debe estar cerrado para generar este archivo.');
        }

        return $periodo;
    }

    /** @return ReporteFactura[] */
    private function reportesFacturaDelPeriodo(PeriodoCierre $periodo): array
    {
        return $this->em->getRepository(ReporteFactura::class)->findBy(['periodoCierre' => $periodo]);
    }

    private function registrarAuditoria(PeriodoCierre $periodo, string $tipo, string $contenido, string $nombreArchivo, int $numeroRegistros): void
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        $plano = new PlanoGenerado($periodo, $tipo, $this->empleadoActual->resolver($usuario), $numeroRegistros, hash('sha256', $contenido), $nombreArchivo);
        $this->em->persist($plano);
        $this->em->flush();
    }

    private function archivo(string $contenido, string $nombreArchivo, string $tipoContenido): Response
    {
        $response = new Response($contenido);
        $response->headers->set('Content-Type', $tipoContenido);
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $nombreArchivo));
        $response->headers->set('Content-Length', (string) strlen($contenido));

        return $response;
    }
}
