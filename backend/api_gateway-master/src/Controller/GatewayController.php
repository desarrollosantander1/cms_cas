<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ProxyService;
use App\Service\RouteResolver;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GatewayController
{
    public function __construct(
        private readonly RouteResolver $routeResolver,
        private readonly ProxyService $proxyService,
    ) {
    }

    public function proxy(Request $request, string $path): Response
    {
        $route = $this->routeResolver->resolve('/' . $path);

        if ($route === null) {
            return new JsonResponse(
                ['error' => 'Ruta no encontrada en el gateway', 'code' => 'ROUTE_NOT_FOUND'],
                Response::HTTP_NOT_FOUND,
            );
        }

        return $this->proxyService->forward($request, $route);
    }

    #[Route('/health', name: 'gateway_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }
}
