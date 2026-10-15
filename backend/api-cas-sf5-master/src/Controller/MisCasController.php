<?php

namespace App\Controller;

use App\Entity\Gbl\Usuario;
use App\Service\CasUsuarioService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CAS activos a los que está asociado el usuario autenticado — alimenta el
 * selector de centro de servicio de "Órdenes" (persistido en localStorage
 * por el frontend, ver stores/cas.ts). ROLE_CAS (no ROLE_CAS_ADMIN): lo
 * necesita cualquier usuario operativo de un CAS, no solo quien administra
 * la configuración de centros de servicio — por eso va en un controller
 * separado de CasController/CasUsuarioController.
 */
#[IsGranted('ROLE_CAS')]
class MisCasController extends AbstractController
{
    public function __construct(private readonly CasUsuarioService $casUsuarioService)
    {
    }

    #[Route('/mis-cas', name: 'cas_mis_cas', methods: ['GET'])]
    public function listar(): JsonResponse
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        return $this->json($this->casUsuarioService->misCas($usuario));
    }
}
