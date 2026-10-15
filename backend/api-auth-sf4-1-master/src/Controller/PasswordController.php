<?php

namespace App\Controller;

use App\Service\PasswordResetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class PasswordController extends AbstractController
{
    public function __construct(private readonly PasswordResetService $passwordResetService)
    {
    }

    #[Route('/password/forgot', name: 'password_forgot', methods: ['POST'])]
    public function forgot(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $usuario = trim((string) ($payload['usuario'] ?? ''));

        if ($usuario !== '') {
            $this->passwordResetService->requestReset($usuario, $request->getClientIp());
        }

        // Respuesta genérica siempre: no revela si el usuario existe.
        return $this->json([
            'message' => 'Si el usuario existe, recibirás un enlace de recuperación en el correo configurado.',
        ]);
    }

    #[Route('/password/reset', name: 'password_reset', methods: ['POST'])]
    public function reset(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $token = (string) ($payload['token'] ?? '');
        $password = (string) ($payload['password'] ?? '');

        if ($token === '' || $password === '') {
            return $this->json(['message' => 'Token y contraseña son requeridos.'], 400);
        }

        if (strlen($password) < 8) {
            return $this->json(['message' => 'La contraseña debe tener al menos 8 caracteres.'], 400);
        }

        try {
            $this->passwordResetService->resetPassword($token, $password);
        } catch (\DomainException $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        }

        return $this->json(['message' => 'Contraseña actualizada correctamente.']);
    }
}
