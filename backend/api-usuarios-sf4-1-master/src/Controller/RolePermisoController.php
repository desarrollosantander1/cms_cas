<?php

namespace App\Controller;

use App\Entity\RolePermiso;
use App\Service\RolePermisoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/roles')]
class RolePermisoController extends AbstractController
{
    public function __construct(private readonly RolePermisoService $rolePermisoService)
    {
    }

    /**
     * Lista plana con role_padre_id — el frontend arma la jerarquía visual
     * (no hay nombre/descripcion aparte: "role" es el único identificador).
     * Incluye activos e inactivos: quien solo necesita asignar roles a un
     * usuario o menú filtra por "estado" del lado del cliente.
     */
    #[Route('', name: 'roles_list', methods: ['GET'])]
    #[IsGranted('ROLE_USUARIO_VER')]
    public function list(): JsonResponse
    {
        return $this->json(array_map($this->serialize(...), $this->rolePermisoService->listar()));
    }

    #[Route('', name: 'roles_create', methods: ['POST'])]
    #[IsGranted('ROLE_GESTIONAR_ROLES')]
    public function create(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $rol = $this->rolePermisoService->crear($payload);

        return $this->json($this->serialize($rol), 201);
    }

    #[Route('/{id}', name: 'roles_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_GESTIONAR_ROLES')]
    public function update(int $id, Request $request): JsonResponse
    {
        $rol = $this->rolePermisoService->buscar($id);
        $payload = json_decode($request->getContent(), true) ?? [];
        $rol = $this->rolePermisoService->actualizar($rol, $payload);

        return $this->json($this->serialize($rol));
    }

    #[Route('/{id}/activar', name: 'roles_activar', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_GESTIONAR_ROLES')]
    public function activar(int $id): JsonResponse
    {
        $rol = $this->rolePermisoService->buscar($id);
        $this->rolePermisoService->activar($rol);

        return $this->json($this->serialize($rol));
    }

    #[Route('/{id}/desactivar', name: 'roles_desactivar', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_GESTIONAR_ROLES')]
    public function desactivar(int $id): JsonResponse
    {
        $rol = $this->rolePermisoService->buscar($id);
        $this->rolePermisoService->desactivar($rol);

        return $this->json($this->serialize($rol));
    }

    private function serialize(RolePermiso $r): array
    {
        return [
            'id' => $r->getId(),
            'role' => $r->getRole(),
            'rolePadreId' => $r->getRolePadre()?->getId(),
            'estado' => $r->isEstado(),
            'nivel' => $r->nivel(),
        ];
    }
}
