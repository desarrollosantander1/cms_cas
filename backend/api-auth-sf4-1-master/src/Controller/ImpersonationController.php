<?php

namespace App\Controller;

use App\Entity\Usuario;
use App\Service\ImpersonationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ImpersonationController extends AbstractController
{
    public function __construct(private readonly ImpersonationService $impersonation)
    {
    }

    #[Route('/impersonate/{id}', name: 'impersonate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function impersonate(int $id, Request $request): JsonResponse
    {
        /** @var Usuario $llamante */
        $llamante = $this->getUser();

        $resultado = $this->impersonation->simular(
            $llamante,
            $id,
            $this->bearerToken($request),
            $request->getClientIp(),
        );

        /** @var Usuario $objetivo */
        $objetivo = $resultado['usuario'];

        return $this->json([
            'token' => $resultado['token'],
            'usuario' => [
                'id' => $objetivo->getId(),
                'usuario' => $objetivo->getUsuario(),
                'email' => $objetivo->getEmail(),
                'nombre' => $objetivo->getNombre(),
                'apellido' => $objetivo->getApellido(),
                'telefono' => $objetivo->getTelefono(),
                'documento' => $objetivo->getDocumento(),
            ],
        ]);
    }

    private function bearerToken(Request $request): string
    {
        $header = $request->headers->get('Authorization', '');

        return str_starts_with($header, 'Bearer ') ? substr($header, 7) : $header;
    }
}
