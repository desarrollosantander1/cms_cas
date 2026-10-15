<?php

namespace App\Controller;

use App\Entity\Usuario;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class SessionController extends AbstractController
{
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(): void
    {
        // Nunca se ejecuta: json_login (security.yaml, firewall "login")
        // intercepta la petición antes de llegar al controlador y responde
        // con el JWT via lexik_jwt_authentication.handler.authentication_success.
        // La ruta debe existir igual para que el router no la resuelva a 404
        // antes de que el firewall tenga oportunidad de interceptarla.
        throw new \LogicException('Esta ruta la intercepta json_login, nunca debería ejecutarse.');
    }

    #[Route('/me', name: 'session_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        return $this->json([
            'id'        => $usuario->getId(),
            'usuario'   => $usuario->getUsuario(),
            'email'     => $usuario->getEmail(),
            'nombre'    => $usuario->getNombre(),
            'apellido'  => $usuario->getApellido(),
            'telefono'  => $usuario->getTelefono(),
            'documento' => $usuario->getDocumento(),
        ]);
    }

    #[Route('/logout', name: 'session_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        // JWT es stateless: no hay nada que invalidar en el servidor.
        // El cliente descarta el token. Punto de extensión si más adelante
        // se agrega blacklist o refresh tokens.
        return $this->json(['status' => 'ok']);
    }

    #[Route('/health', name: 'health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return $this->json(['status' => 'ok']);
    }
}
