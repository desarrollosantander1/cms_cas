<?php

namespace App\Security;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Reenvía el mismo Bearer del llamante a GET {USERS_SERVICE_URL}/me — ese
 * endpoint ya resuelve los permisos efectivos (CTE sobre role_permiso) y
 * users ya confía en cualquier JWT firmado por esta misma auth, así que no
 * hace falta ningún secreto compartido nuevo entre servicios.
 */
class UsersServicePermissionChecker implements RemotePermissionCheckerInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $usersServiceUrl,
    ) {
    }

    public function tienePermiso(string $bearerToken, string $role): bool
    {
        try {
            $response = $this->httpClient->request('GET', rtrim($this->usersServiceUrl, '/') . '/me', [
                'headers' => ['Authorization' => 'Bearer ' . $bearerToken],
                'timeout' => 5,
            ]);

            $roles = $response->toArray(false)['roles'] ?? [];

            return in_array($role, $roles, true);
        } catch (HttpClientExceptionInterface $e) {
            $this->logger->error('No se pudo verificar el permiso contra API users sf4-1.', [
                'role' => $role,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
