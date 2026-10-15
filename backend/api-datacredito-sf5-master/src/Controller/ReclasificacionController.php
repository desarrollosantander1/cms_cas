<?php

namespace App\Controller;

use App\Entity\Gbl\Usuario;
use App\Service\PeriodoFacturaService;
use App\Service\ReclasificacionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ReclasificacionController extends AbstractController
{
    public function __construct(
        private readonly PeriodoFacturaService $periodoFacturas,
        private readonly ReclasificacionService $reclasificaciones,
    ) {
    }

    #[Route('/reclasificados/clientes', name: 'datacredito_reclasificados_clientes', methods: ['GET'])]
    #[IsGranted('ROLE_RECLASIFICADO_VER')]
    public function listarClientes(Request $request): JsonResponse
    {
        return $this->json($this->periodoFacturas->listarClientesReclasificados($this->fechaReporteDeQuery($request)));
    }

    #[Route('/reclasificados/clientes/{tercero}/facturas', name: 'datacredito_reclasificados_facturas_cliente', methods: ['GET'])]
    #[IsGranted('ROLE_RECLASIFICADO_VER')]
    public function listarFacturas(string $tercero, Request $request): JsonResponse
    {
        return $this->json($this->periodoFacturas->listarFacturasReclasificadas($tercero, $this->fechaReporteDeQuery($request)));
    }

    /**
     * Sin chequeo de rol específico a propósito: crear una reclasificación
     * depende solo de estar en la pestaña Datacrédito (ver FacturasModal.vue),
     * no de un permiso — cualquier usuario autenticado (IS_AUTHENTICATED_FULLY,
     * ya exigido por el firewall) puede llamar este endpoint.
     */
    #[Route('/reclasificados/clientes/{tercero}/facturas', name: 'datacredito_reclasificados_guardar', methods: ['POST'])]
    public function guardar(string $tercero, Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        foreach (['fechaReporte', 'facturas'] as $campo) {
            if (!isset($payload[$campo])) {
                throw new BadRequestHttpException(sprintf('El campo "%s" es requerido.', $campo));
            }
        }

        /** @var Usuario $usuario */
        $usuario = $this->getUser();
        $motivo = isset($payload['motivo']) && trim((string) $payload['motivo']) !== '' ? (string) $payload['motivo'] : null;
        $resultado = $this->reclasificaciones->guardar($tercero, (string) $payload['fechaReporte'], $payload['facturas'], $usuario, $motivo);

        return $this->json(['message' => 'Se guardaron las reclasificaciones correctamente.', ...$resultado]);
    }

    #[Route('/reclasificados/facturas', name: 'datacredito_reclasificados_todas', methods: ['GET'])]
    #[IsGranted('ROLE_RECLASIFICADO_VER')]
    public function listarTodas(Request $request): JsonResponse
    {
        return $this->json($this->periodoFacturas->listarTodasFacturasReclasificadas($this->fechaReporteDeQuery($request)));
    }

    #[Route('/reclasificados/facturas', name: 'datacredito_reclasificados_guardar_todas', methods: ['POST'])]
    #[IsGranted('ROLE_RECLASIFICADO_VER')]
    public function guardarTodas(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        foreach (['fechaReporte', 'facturas'] as $campo) {
            if (!isset($payload[$campo])) {
                throw new BadRequestHttpException(sprintf('El campo "%s" es requerido.', $campo));
            }
        }

        /** @var Usuario $usuario */
        $usuario = $this->getUser();
        $resultado = $this->reclasificaciones->guardarTodas((string) $payload['fechaReporte'], $payload['facturas'], $usuario);

        return $this->json(['message' => sprintf('Se guardaron %d facturas correctamente.', $resultado['totalFacturas']), ...$resultado]);
    }

    private function fechaReporteDeQuery(Request $request): string
    {
        $fechaReporte = $request->query->get('fechaReporte');
        if ($fechaReporte === null) {
            throw new BadRequestHttpException('El parámetro "fechaReporte" es requerido.');
        }

        return $fechaReporte;
    }
}
