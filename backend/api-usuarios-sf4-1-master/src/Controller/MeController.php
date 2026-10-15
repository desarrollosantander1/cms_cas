<?php

namespace App\Controller;

use App\Entity\Usuario;
use App\Service\MenuTreeService;
use App\Service\PermisosEfectivosService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class MeController extends AbstractController
{
    public function __construct(
        private readonly PermisosEfectivosService $permisosEfectivos,
        private readonly MenuTreeService $menuTree,
    ) {
    }

    /**
     * Unifica lo que antes eran /me/permisos y /me/menu en una sola
     * petición (el frontend las pedía siempre juntas al arrancar la sesión,
     * duplicando un round-trip HTTP en cada F5). Ambas piezas salen de la
     * misma llamada a PermisosEfectivosService, que ya cachea internamente
     * los efectivos por usuario — no hay costo extra de base de datos por
     * combinarlas.
     */
    #[Route('/me', name: 'me_bootstrap', methods: ['GET'])]
    public function bootstrap(): JsonResponse
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        return $this->json([
            'roles' => $this->permisosEfectivos->paraUsuario($usuario),
            'menu' => $this->menuTree->arbolPodado($this->permisosEfectivos->idsParaUsuario($usuario)),
        ]);
    }
}
