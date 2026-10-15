<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\Model\SecurityScheme;
use ApiPlatform\OpenApi\OpenApi;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * Documenta en el OpenAPI generado por API Platform las rutas de negocio
 * reales de este servicio (controladores en App\Controller), sin exponer
 * las entidades Doctrine como #[ApiResource] con CRUD automático — la
 * lógica de negocio (roles/permisos, menús, gestión de usuarios) sigue
 * viviendo exclusivamente en los controladores/servicios existentes.
 */
final class OpenApiFactory implements OpenApiFactoryInterface
{
    /** Prefijos de path que en security.yaml tienen `roles: PUBLIC_ACCESS`. */
    private const PUBLIC_PATH_PREFIXES = ['/health'];

    public function __construct(
        private readonly OpenApiFactoryInterface $decorated,
        private readonly RouterInterface $router,
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $paths = $openApi->getPaths();

        foreach ($this->router->getRouteCollection()->all() as $name => $route) {
            if (!$this->isDocumentableRoute($name, $route)) {
                continue;
            }

            $this->addRouteToPaths($paths, $name, $route);
        }

        return $openApi->withComponents($this->withBearerAuth($openApi->getComponents()));
    }

    private function addRouteToPaths(\ApiPlatform\OpenApi\Model\Paths $paths, string $name, Route $route): void
    {
        $path = $route->getPath();
        $methods = $route->getMethods() ?: ['GET'];
        $tag = $this->tagFor((string) $route->getDefault('_controller'));
        $security = $this->isPublicPath($path) ? [] : [['bearerAuth' => []]];

        $existing = $paths->getPath($path);
        $slots = [
            'get' => $existing?->getGet(),
            'put' => $existing?->getPut(),
            'post' => $existing?->getPost(),
            'delete' => $existing?->getDelete(),
            'patch' => $existing?->getPatch(),
        ];

        foreach ($methods as $method) {
            $key = strtolower($method);
            if (!\array_key_exists($key, $slots)) {
                continue;
            }

            $slots[$key] = new Operation(
                operationId: $name,
                tags: [$tag],
                responses: ['200' => new Response('OK')],
                summary: $name,
                security: $security,
            );
        }

        $paths->addPath($path, new PathItem(
            get: $slots['get'],
            put: $slots['put'],
            post: $slots['post'],
            delete: $slots['delete'],
            patch: $slots['patch'],
        ));
    }

    private function isDocumentableRoute(string $name, Route $route): bool
    {
        if (str_starts_with($name, '_') || str_starts_with($name, 'api_')) {
            return false;
        }

        $controller = $route->getDefault('_controller');

        return \is_string($controller) && str_starts_with($controller, 'App\\Controller\\');
    }

    /**
     * "App\Controller\Sync\IngestaClienteController::__invoke" -> "Sync"
     * "App\Controller\ReporteController::txt" -> "Reporte"
     */
    private function tagFor(string $controller): string
    {
        $class = explode('::', $controller)[0];
        $parts = explode('\\', $class);
        array_shift($parts); // App
        array_shift($parts); // Controller
        $short = (string) array_pop($parts);
        $short = preg_replace('/Controller$/', '', $short) ?? $short;

        return $parts ? implode('/', $parts) : $short;
    }

    private function isPublicPath(string $path): bool
    {
        foreach (self::PUBLIC_PATH_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function withBearerAuth(Components $components): Components
    {
        $schemes = $components->getSecuritySchemes() ?? new \ArrayObject();
        $schemes['bearerAuth'] = new SecurityScheme(
            type: 'http',
            description: 'Token JWT enviado como "Authorization: Bearer <token>".',
            scheme: 'bearer',
            bearerFormat: 'JWT',
        );

        return new Components(
            schemas: $components->getSchemas(),
            responses: $components->getResponses(),
            parameters: $components->getParameters(),
            examples: $components->getExamples(),
            requestBodies: $components->getRequestBodies(),
            headers: $components->getHeaders(),
            securitySchemes: $schemes,
            links: $components->getLinks(),
            callbacks: $components->getCallbacks(),
            pathItems: $components->getPathItems(),
        );
    }
}
