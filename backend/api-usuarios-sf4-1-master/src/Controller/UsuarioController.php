<?php

namespace App\Controller;

use App\Entity\Usuario;
use App\Entity\UsuarioRolePermiso;
use App\Service\PermisosEfectivosService;
use App\Service\UsuarioService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/usuarios')]
class UsuarioController extends AbstractController
{
    public function __construct(
        private readonly UsuarioService $usuarioService,
        private readonly PermisosEfectivosService $permisosEfectivos,
    ) {
    }

    #[Route('', name: 'usuarios_list', methods: ['GET'])]
    #[IsGranted('ROLE_USUARIO_VER')]
    public function list(Request $request): JsonResponse
    {
        $busqueda = trim((string) $request->query->get('q', ''));
        $items = array_map($this->serialize(...), $this->usuarioService->listar($busqueda));
        return $this->json($items);
    }

    /**
     * Lista liviana para el selector de "simular usuario" — deliberadamente
     * separada de list(): exige ROLE_SIMULAR_USUARIO en vez de
     * ROLE_USUARIO_VER (son permisos independientes) y no expone roles ni
     * contador de fallos, solo lo necesario para elegir a quién simular.
     */
    #[Route('/simulables', name: 'usuarios_simulables', methods: ['GET'])]
    #[IsGranted('ROLE_SIMULAR_USUARIO')]
    public function simulables(): JsonResponse
    {
        /** @var Usuario $llamante */
        $llamante = $this->getUser();

        $items = array_map(
            fn (Usuario $u) => [
                'id' => $u->getId(),
                'usuario' => $u->getUsuario(),
                'nombre' => $u->getNombre(),
                'apellido' => $u->getApellido(),
                'email' => $u->getEmail(),
            ],
            $this->usuarioService->activosParaSimular($llamante),
        );

        return $this->json($items);
    }

    #[Route('', name: 'usuarios_create', methods: ['POST'])]
    #[IsGranted('ROLE_USUARIO_CREAR')]
    public function create(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $usuario = $this->usuarioService->crear($payload);

        return $this->json($this->serialize($usuario), 201);
    }

    #[Route('/{id}', name: 'usuarios_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USUARIO_VER')]
    public function get(int $id): JsonResponse
    {
        return $this->json($this->serialize($this->usuarioService->buscar($id)));
    }

    #[Route('/{id}', name: 'usuarios_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USUARIO_EDITAR')]
    public function update(int $id, Request $request): JsonResponse
    {
        $usuario = $this->usuarioService->buscar($id);
        $payload = json_decode($request->getContent(), true) ?? [];
        $usuario = $this->usuarioService->actualizar($usuario, $payload);

        return $this->json($this->serialize($usuario));
    }

    #[Route('/{id}/activar', name: 'usuarios_activar', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USUARIO_EDITAR')]
    public function activar(int $id): JsonResponse
    {
        $usuario = $this->usuarioService->buscar($id);
        $this->usuarioService->activar($usuario);

        return $this->json($this->serialize($usuario));
    }

    #[Route('/{id}/desactivar', name: 'usuarios_desactivar', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USUARIO_ELIMINAR')]
    public function desactivar(int $id): JsonResponse
    {
        $usuario = $this->usuarioService->buscar($id);
        $this->usuarioService->desactivar($usuario);

        return $this->json($this->serialize($usuario));
    }

    #[Route('/{id}/password', name: 'usuarios_password', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USUARIO_EDITAR')]
    public function cambiarPassword(int $id, Request $request): JsonResponse
    {
        $usuario = $this->usuarioService->buscar($id);
        $payload = json_decode($request->getContent(), true) ?? [];
        $this->usuarioService->cambiarPassword($usuario, (string) ($payload['password'] ?? ''));

        return $this->json(['message' => 'Contraseña actualizada.']);
    }

    #[Route('/{id}/roles/{rolePermisoId}', name: 'usuarios_roles_asignar', methods: ['POST'], requirements: ['id' => '\d+', 'rolePermisoId' => '\d+'])]
    #[IsGranted('ROLE_USUARIO_EDITAR')]
    public function asignarRol(int $id, int $rolePermisoId): JsonResponse
    {
        $this->usuarioService->asignarRol($this->usuarioService->buscar($id), $this->usuarioService->buscarRol($rolePermisoId));

        return $this->json(['message' => 'Rol asignado.']);
    }

    #[Route('/{id}/roles/{rolePermisoId}', name: 'usuarios_roles_quitar', methods: ['DELETE'], requirements: ['id' => '\d+', 'rolePermisoId' => '\d+'])]
    #[IsGranted('ROLE_USUARIO_EDITAR')]
    public function quitarRol(int $id, int $rolePermisoId): JsonResponse
    {
        $this->usuarioService->quitarRol($this->usuarioService->buscar($id), $this->usuarioService->buscarRol($rolePermisoId));

        return $this->json(['message' => 'Rol removido.']);
    }

    #[Route('/{id}/permisos-efectivos', name: 'usuarios_permisos_efectivos', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USUARIO_VER')]
    public function permisosEfectivos(int $id): JsonResponse
    {
        $usuario = $this->usuarioService->buscar($id);

        return $this->json(['roles' => $this->permisosEfectivos->paraUsuario($usuario)]);
    }

    private function serialize(Usuario $usuario): array
    {
        return [
            'id' => $usuario->getId(),
            'documento' => $usuario->getDocumento(),
            'nombre' => $usuario->getNombre(),
            'apellido' => $usuario->getApellido(),
            'telefono' => $usuario->getTelefono(),
            'usuario' => $usuario->getUsuario(),
            'email' => $usuario->getEmail(),
            'estado' => $usuario->isEstado(),
            'fallos' => $usuario->getFallos(),
            'roles' => array_map(
                fn (UsuarioRolePermiso $urp) => ['id' => $urp->getRolePermiso()->getId(), 'role' => $urp->getRolePermiso()->getRole()],
                $this->usuarioService->rolesActivos($usuario),
            ),
        ];
    }
}
