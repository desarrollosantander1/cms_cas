<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Decide, a partir de la tabla de rutas configurada en config/packages/gateway.yaml,
 * a qué microservicio debe reenviarse una petición entrante y cuál es el path
 * resultante en ese microservicio.
 */
final class RouteResolver
{
    /**
     * @param array<int, array{prefix: string, target: string, strip_prefix: bool}> $routes
     */
    public function __construct(
        private readonly array $routes,
    ) {
    }

    public function resolve(string $requestPath): ?ResolvedRoute
    {
        $requestPath = '/' . ltrim($requestPath, '/');

        foreach ($this->routes as $route) {
            $prefix = rtrim($route['prefix'], '/');

            if ($requestPath === $prefix || str_starts_with($requestPath, $prefix . '/')) {
                $targetPath = $requestPath;

                if ($route['strip_prefix'] ?? false) {
                    $targetPath = substr($requestPath, strlen($prefix));
                    if ($targetPath === '') {
                        $targetPath = '/';
                    }
                }

                return new ResolvedRoute(
                    targetBaseUrl: rtrim($route['target'], '/'),
                    targetPath: $targetPath,
                );
            }
        }

        return null;
    }
}
