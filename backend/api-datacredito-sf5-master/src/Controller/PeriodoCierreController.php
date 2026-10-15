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

class PeriodoCierreController extends AbstractController
{
    public function __construct(private readonly PeriodoFacturaService $periodoFacturas)
    {
    }

    /**
     * Sin #[IsGranted] de un solo rol: la lista de períodos es compartida
     * por las dos pestañas del módulo (Datacrédito y Reclasificados, ver
     * DataCreditoAdmin.vue) — cualquiera de los dos roles de "ver" basta.
     * PermisoVoter no soporta expresiones OR en el atributo (solo nombres
     * de rol individuales, ver su docblock), de ahí el chequeo manual en
     * vez de un #[IsGranted('ROLE_A or ROLE_B')] que no funcionaría.
     */
    #[Route('/periodos', name: 'datacredito_periodos_list', methods: ['GET'])]
    public function listar(): JsonResponse
    {
        if (!$this->isGranted('ROLE_DATACREDITO_VER') && !$this->isGranted('ROLE_RECLASIFICADO_VER')) {
            throw $this->createAccessDeniedException();
        }

        return $this->json($this->periodoFacturas->listarPeriodos());
    }

    #[Route('/periodos', name: 'datacredito_periodos_create', methods: ['POST'])]
    #[IsGranted('ROLE_DATACREDITO_EDITAR')]
    public function crear(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        if (!isset($payload['fechaReporte'])) {
            throw new BadRequestHttpException('El campo "fechaReporte" es requerido.');
        }

        return $this->json($this->periodoFacturas->crearPeriodo((string) $payload['fechaReporte']), 201);
    }

    #[Route('/cierre', name: 'datacredito_cierre', methods: ['POST'])]
    #[IsGranted('ROLE_DATACREDITO_CERRAR')]
    public function cerrar(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        foreach (['periodoId', 'fechaReporte'] as $campo) {
            if (!isset($payload[$campo])) {
                throw new BadRequestHttpException(sprintf('El campo "%s" es requerido.', $campo));
            }
        }

        /** @var Usuario $usuario */
        $usuario = $this->getUser();
        $resultado = $this->periodoFacturas->cerrarPeriodo((int) $payload['periodoId'], (string) $payload['fechaReporte'], $usuario);

        return $this->json(['message' => 'Período cerrado correctamente.', ...$resultado]);
    }
}
