<?php

namespace App\Controller;

use App\Entity\CasUsuario;
use App\Entity\Gbl\Usuario;
use App\Service\CasUsuarioService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Tab "Usuarios" del prompt original: una fila por relación cas_usuario, y
 * búsqueda de usuarios (existan o no) por documento. La edición de un
 * usuario ya asociado vive en CasController — necesita el id del CAS en la
 * ruta porque el estado que se edita es el de esa relación puntual, no el
 * global de gbl.usuario (ver CasUsuarioService::actualizarUsuario()).
 * Prefijo "/usuarios" (no "/cas/usuarios"): el gateway ya enruta /api/cas
 * aquí y le quita ese prefijo (ver CasController).
 */
#[Route('/usuarios')]
#[IsGranted('ROLE_CAS_ADMIN')]
class CasUsuarioController extends AbstractController
{
    public function __construct(private readonly CasUsuarioService $casUsuarioService)
    {
    }

    #[Route('', name: 'cas_usuarios_listar_todos', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json(array_map($this->serialize(...), $this->casUsuarioService->listarTodos()));
    }

    /** Para precargar el modal de asociación al buscar por documento (punto 79-82). */
    #[Route('/buscar', name: 'cas_usuarios_buscar', methods: ['GET'])]
    public function buscar(Request $request): JsonResponse
    {
        $documento = trim((string) $request->query->get('documento', ''));
        if ($documento === '') {
            return $this->json(['usuario' => null, 'casAsociados' => []]);
        }

        $resultado = $this->casUsuarioService->buscarPorDocumento($documento);
        $usuario = $resultado['usuario'];

        return $this->json([
            'usuario' => $usuario !== null ? $this->serializeUsuario($usuario) : null,
            'casAsociados' => array_map(
                fn (CasUsuario $cu) => ['casId' => $cu->getCas()->getId(), 'casNombre' => $cu->getCas()->nombreMostrar()],
                $resultado['casUsuarios'],
            ),
        ]);
    }

    private function serialize(CasUsuario $casUsuario): array
    {
        $usuario = $casUsuario->getUsuario();
        $cas     = $casUsuario->getCas();

        return [
            'casUsuarioId'       => $casUsuario->getId(),
            'usuarioId'          => $usuario->getId(),
            'usuario'            => $usuario->getUsuario(),
            'documento'          => $usuario->getDocumento(),
            'nombre'             => trim(($usuario->getNombre() ?? '').' '.($usuario->getApellido() ?? '')),
            'casId'              => $cas->getId(),
            'casUsuarioEstado'   => $casUsuario->isEstado(),
            'casUsuarioCreadoEn' => $casUsuario->getCreadoEn(),
            'casNit'             => $cas->nitMostrar(),
            'casNombre'          => $cas->nombreMostrar(),
        ];
    }

    private function serializeUsuario(Usuario $usuario): array
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
        ];
    }
}
