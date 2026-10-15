<?php

namespace App\Controller;

use App\Entity\Menu;
use App\Entity\RolePermiso;
use App\Repository\RolePermisoRepository;
use App\Service\MenuRolePermisoService;
use App\Service\MenuService;
use App\Service\MenuTreeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/menus')]
class MenuController extends AbstractController
{
    public function __construct(
        private readonly MenuTreeService $menuTree,
        private readonly MenuService $menuService,
        private readonly MenuRolePermisoService $menuRolePermisoService,
        private readonly RolePermisoRepository $rolesPermiso,
    ) {
    }

    /** Árbol completo (activos e inactivos) para la pantalla de gestión de menú. */
    #[Route('', name: 'menus_arbol', methods: ['GET'])]
    #[IsGranted('ROLE_GESTIONAR_MENU')]
    public function arbol(): JsonResponse
    {
        return $this->json($this->menuTree->arbolCompleto());
    }

    #[Route('', name: 'menus_create', methods: ['POST'])]
    #[IsGranted('ROLE_GESTIONAR_MENU')]
    public function create(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $menu = $this->menuService->crear($payload);

        return $this->json($this->serialize($menu), 201);
    }

    #[Route('/{id}', name: 'menus_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_GESTIONAR_MENU')]
    public function update(int $id, Request $request): JsonResponse
    {
        $menu = $this->menuService->buscar($id);
        $payload = json_decode($request->getContent(), true) ?? [];
        $menu = $this->menuService->actualizar($menu, $payload);

        return $this->json($this->serialize($menu));
    }

    #[Route('/{id}/activar', name: 'menus_activar', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_GESTIONAR_MENU')]
    public function activar(int $id): JsonResponse
    {
        $menu = $this->menuService->buscar($id);
        $this->menuService->activar($menu);

        return $this->json($this->serialize($menu));
    }

    #[Route('/{id}/desactivar', name: 'menus_desactivar', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_GESTIONAR_MENU')]
    public function desactivar(int $id): JsonResponse
    {
        $menu = $this->menuService->buscar($id);
        $this->menuService->desactivar($menu);

        return $this->json($this->serialize($menu));
    }

    /** Borrado físico del menú y, si es un menú padre, en cascada de sus submenús y las relaciones de rol de ambos. */
    #[Route('/{id}', name: 'menus_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ELIMINAR_MENU')]
    public function delete(int $id): JsonResponse
    {
        $menu = $this->menuService->buscar($id);
        $this->menuService->eliminar($menu);

        return $this->json(['message' => 'Menú eliminado.']);
    }

    #[Route('/{id}/roles', name: 'menus_roles_list', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_GESTIONAR_MENU')]
    public function rolesList(int $id): JsonResponse
    {
        $menu = $this->menuService->buscar($id);

        return $this->json(array_map($this->serializeRol(...), $this->menuRolePermisoService->rolesDe($menu)));
    }

    #[Route('/{id}/roles/{rolePermisoId}', name: 'menus_roles_asociar', methods: ['POST'], requirements: ['id' => '\d+', 'rolePermisoId' => '\d+'])]
    #[IsGranted('ROLE_GESTIONAR_MENU')]
    public function asociarRol(int $id, int $rolePermisoId): JsonResponse
    {
        $this->menuRolePermisoService->asociar($this->menuService->buscar($id), $this->encontrarRolOFallar($rolePermisoId));

        return $this->json(['message' => 'Rol asociado al menú.']);
    }

    #[Route('/{id}/roles/{rolePermisoId}', name: 'menus_roles_desasociar', methods: ['DELETE'], requirements: ['id' => '\d+', 'rolePermisoId' => '\d+'])]
    #[IsGranted('ROLE_GESTIONAR_MENU')]
    public function desasociarRol(int $id, int $rolePermisoId): JsonResponse
    {
        $this->menuRolePermisoService->desasociar($this->menuService->buscar($id), $this->encontrarRolOFallar($rolePermisoId));

        return $this->json(['message' => 'Rol desasociado del menú.']);
    }

    private function encontrarRolOFallar(int $id): RolePermiso
    {
        $rol = $this->rolesPermiso->find($id);
        if ($rol === null) {
            throw new NotFoundHttpException('Rol no encontrado.');
        }

        return $rol;
    }

    private function serialize(Menu $menu): array
    {
        return [
            'id' => $menu->getId(),
            'menu' => $menu->getMenu(),
            'menuGrupoId' => $menu->getMenuGrupo()->getId(),
            'menuPadreId' => $menu->getMenuPadre()?->getId(),
            'icono' => $menu->getIcono(),
            'ruta' => $menu->getRuta(),
            'estado' => $menu->isEstado(),
            'orden' => $menu->getOrden(),
        ];
    }

    private function serializeRol(RolePermiso $rol): array
    {
        return ['id' => $rol->getId(), 'role' => $rol->getRole()];
    }
}
