<?php

namespace App\Controller;

use App\Entity\MenuGrupo;
use App\Service\MenuGrupoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/menu-grupos')]
class MenuGrupoController extends AbstractController
{
    public function __construct(private readonly MenuGrupoService $menuGrupoService)
    {
    }

    #[Route('', name: 'menu_grupos_list', methods: ['GET'])]
    #[IsGranted('ROLE_GESTIONAR_MENU')]
    public function list(): JsonResponse
    {
        return $this->json(array_map($this->serialize(...), $this->menuGrupoService->listar()));
    }

    #[Route('', name: 'menu_grupos_create', methods: ['POST'])]
    #[IsGranted('ROLE_GESTIONAR_MENU')]
    public function create(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $grupo = $this->menuGrupoService->crear($payload);

        return $this->json($this->serialize($grupo), 201);
    }

    #[Route('/{id}', name: 'menu_grupos_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_GESTIONAR_MENU')]
    public function update(int $id, Request $request): JsonResponse
    {
        $grupo = $this->menuGrupoService->buscar($id);
        $payload = json_decode($request->getContent(), true) ?? [];
        $grupo = $this->menuGrupoService->actualizar($grupo, $payload);

        return $this->json($this->serialize($grupo));
    }

    #[Route('/{id}/activar', name: 'menu_grupos_activar', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_GESTIONAR_MENU')]
    public function activar(int $id): JsonResponse
    {
        $grupo = $this->menuGrupoService->buscar($id);
        $this->menuGrupoService->activar($grupo);

        return $this->json($this->serialize($grupo));
    }

    #[Route('/{id}/desactivar', name: 'menu_grupos_desactivar', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_GESTIONAR_MENU')]
    public function desactivar(int $id): JsonResponse
    {
        $grupo = $this->menuGrupoService->buscar($id);
        $this->menuGrupoService->desactivar($grupo);

        return $this->json($this->serialize($grupo));
    }

    /** Borrado físico del grupo y, en cascada, de sus menús, submenús y relaciones de rol. */
    #[Route('/{id}', name: 'menu_grupos_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ELIMINAR_GRUPO')]
    public function delete(int $id): JsonResponse
    {
        $grupo = $this->menuGrupoService->buscar($id);
        $this->menuGrupoService->eliminar($grupo);

        return $this->json(['message' => 'Grupo eliminado.']);
    }

    private function serialize(MenuGrupo $grupo): array
    {
        return [
            'id' => $grupo->getId(),
            'grupo' => $grupo->getGrupo(),
            'icono' => $grupo->getIcono(),
            'estado' => $grupo->isEstado(),
            'orden' => $grupo->getOrden(),
        ];
    }
}
