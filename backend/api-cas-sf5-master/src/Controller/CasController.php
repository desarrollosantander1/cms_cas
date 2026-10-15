<?php

namespace App\Controller;

use App\Entity\Cas;
use App\Entity\CasUsuario;
use App\Service\CasService;
use App\Service\CasUsuarioService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Rutas raíz a propósito (sin prefijo "/cas"): el gateway ya enruta todo
 * el prefijo público /api/cas a este microservicio y le quita ese prefijo
 * (strip_prefix: true, ver gateway.yaml) — repetirlo aquí produciría
 * /api/cas/cas, igual que /usuarios en API users sf4-1 no repite "/users".
 */
#[IsGranted('ROLE_CAS_ADMIN')]
class CasController extends AbstractController
{
    public function __construct(
        private readonly CasService $casService,
        private readonly CasUsuarioService $casUsuarioService,
    ) {
    }

    #[Route('', name: 'cas_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json(array_map($this->serialize(...), $this->casService->listar()));
    }

    #[Route('', name: 'cas_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $cas = $this->casService->crear($payload);

        return $this->json($this->serialize($cas), 201);
    }

    #[Route('/{id}', name: 'cas_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(int $id): JsonResponse
    {
        return $this->json($this->serialize($this->casService->buscar($id)));
    }

    #[Route('/{id}', name: 'cas_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $cas = $this->casService->buscar($id);
        $payload = json_decode($request->getContent(), true) ?? [];
        $cas = $this->casService->actualizar($cas, $payload);

        return $this->json($this->serialize($cas));
    }

    #[Route('/{id}/usuarios', name: 'cas_usuarios_list', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function usuarios(int $id): JsonResponse
    {
        $cas = $this->casService->buscar($id);

        return $this->json(array_map($this->serializeCasUsuario(...), $this->casUsuarioService->listarPorCas($cas)));
    }

    #[Route('/{id}/usuarios', name: 'cas_usuarios_asociar', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function asociarUsuario(int $id, Request $request): JsonResponse
    {
        $cas = $this->casService->buscar($id);
        $payload = json_decode($request->getContent(), true) ?? [];
        $casUsuario = $this->casUsuarioService->asociar($cas, $payload);

        return $this->json($this->serializeCasUsuario($casUsuario), 201);
    }

    /**
     * Editar un usuario asociado a ESTE CAS — el id del CAS va en la ruta
     * (no solo el del usuario) porque `estado` ya no es el estado global de
     * gbl.usuario, sino el de la relación cas.cas_usuario de este CAS con
     * este usuario (ver CasUsuarioService::actualizarUsuario()).
     */
    #[Route('/{id}/usuarios/{usuarioId}', name: 'cas_usuarios_actualizar', methods: ['PUT'], requirements: ['id' => '\d+', 'usuarioId' => '\d+'])]
    public function actualizarUsuario(int $id, int $usuarioId, Request $request): JsonResponse
    {
        $cas = $this->casService->buscar($id);
        $payload = json_decode($request->getContent(), true) ?? [];
        $casUsuario = $this->casUsuarioService->actualizarUsuario($cas, $usuarioId, $payload);

        return $this->json($this->serializeCasUsuario($casUsuario));
    }

    private function serialize(Cas $cas): array
    {
        $sucursal = $cas->getClienteSucursal();
        $cliente = $sucursal?->getCliente();
        $ciudad = $cas->getCiudad();

        return [
            'id' => $cas->getId(),
            'nit' => $cas->nitMostrar(),
            'nombre' => $cas->nombreMostrar(),
            'direccion' => $cas->direccionMostrar(),
            'telefono' => $cas->telefonoMostrar(),
            'cartaCambioVence' => $cas->getCartaCambioVence(),
            'estado' => $cas->isEstado(),
            'creadoEn' => $cas->getCreadoEn()->format(\DateTimeInterface::ATOM),
            'nitPropio' => $cas->getNit(),
            'nombrePropio' => $cas->getNombre(),
            'direccionPropio' => $cas->getDireccion(),
            'telefonoPropio' => $cas->getTelefono(),
            'clienteSucursalId' => $sucursal?->getId(),
            'clienteId' => $cliente?->getId(),
            'clienteNombre' => $cliente?->getNombreMostrar(),
            'sucursalNombre' => $sucursal?->getNombre(),
            'ciudadId' => $ciudad?->getId(),
            'ciudadNombre' => $ciudad?->getNombre(),
        ];
    }

    private function serializeCasUsuario(CasUsuario $casUsuario): array
    {
        $usuario = $casUsuario->getUsuario();

        return [
            'id' => $casUsuario->getId(),
            'cas' => $casUsuario->getCas()->nombreMostrar(),
            'casId' => $casUsuario->getCas()->getId(),
            'usuarioId' => $usuario->getId(),
            'usuario' => $usuario->getUsuario(),
            'documento' => $usuario->getDocumento(),
            'nombre' => trim(($usuario->getNombre() ?? '').' '.($usuario->getApellido() ?? '')),
            // Estado de la relación cas_usuario (este CAS con este usuario),
            // no de gbl.usuario: un usuario puede estar activo en un CAS e
            // inactivo en otro — ver CasUsuarioService::actualizarUsuario().
            'estado' => $casUsuario->isEstado(),
            'creadoEn' => $casUsuario->getCreadoEn()->format(\DateTimeInterface::ATOM),
        ];
    }
}
