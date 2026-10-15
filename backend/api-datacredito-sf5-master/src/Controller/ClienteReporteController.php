<?php

namespace App\Controller;

use App\Entity\Gbl\Usuario;
use App\Service\PeriodoFacturaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ClienteReporteController extends AbstractController
{
    public function __construct(private readonly PeriodoFacturaService $periodoFacturas)
    {
    }

    #[Route('/clientes', name: 'datacredito_clientes_list', methods: ['GET'])]
    #[IsGranted('ROLE_DATACREDITO_VER')]
    public function listarClientes(Request $request): JsonResponse
    {
        return $this->json($this->periodoFacturas->listarClientes($this->fechaReporteDeQuery($request)));
    }

    #[Route('/clientes/{tercero}/facturas', name: 'datacredito_facturas_cliente', methods: ['GET'])]
    #[IsGranted('ROLE_DATACREDITO_VER')]
    public function listarFacturas(string $tercero, Request $request): JsonResponse
    {
        return $this->json($this->periodoFacturas->listarFacturasCliente($tercero, $this->fechaReporteDeQuery($request)));
    }

    #[Route('/resumen-novedades', name: 'datacredito_resumen_novedades', methods: ['GET'])]
    #[IsGranted('ROLE_DATACREDITO_VER')]
    public function resumenNovedades(Request $request): JsonResponse
    {
        return $this->json($this->periodoFacturas->resumenNovedades($this->fechaReporteDeQuery($request)));
    }

    #[Route('/clientes/{tercero}/novedades', name: 'datacredito_guardar_novedades', methods: ['POST'])]
    #[IsGranted('ROLE_DATACREDITO_EDITAR')]
    public function guardarNovedades(string $tercero, Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        foreach (['fechaReporte', 'facturas'] as $campo) {
            if (!isset($payload[$campo])) {
                throw new BadRequestHttpException(sprintf('El campo "%s" es requerido.', $campo));
            }
        }

        /** @var Usuario $usuario */
        $usuario = $this->getUser();
        $resultado = $this->periodoFacturas->guardarNovedades($tercero, (string) $payload['fechaReporte'], $payload['facturas'], $usuario);

        return $this->json(['message' => 'Se reportaron las facturas correctamente.', ...$resultado]);
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
