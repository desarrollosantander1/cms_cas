<?php

namespace App\Service;

use App\Entity\Usuario;
use App\Entity\UsuarioLog;
use App\Repository\UsuarioRepository;
use App\Security\RemotePermissionCheckerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Caso de uso "simular usuario": emite un JWT para otro usuario activo a
 * nombre de quien lo pide, sin tocar la sesión de quien lo pide — el
 * intercambio de qué token queda activo en el navegador es responsabilidad
 * del frontend (stores/auth.ts), no de este servicio.
 */
class ImpersonationService
{
    private const ROL_REQUERIDO = 'ROLE_SIMULAR_USUARIO';

    public function __construct(
        private readonly RemotePermissionCheckerInterface $permisos,
        private readonly UsuarioRepository $usuarios,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /** @return array{token: string, usuario: Usuario} */
    public function simular(Usuario $llamante, int $objetivoId, string $bearerToken, ?string $requestIp): array
    {
        if (!$this->permisos->tienePermiso($bearerToken, self::ROL_REQUERIDO)) {
            throw new AccessDeniedException('No tienes permiso para simular usuarios.');
        }

        $objetivo = $this->usuarios->find($objetivoId);
        if ($objetivo === null) {
            throw new NotFoundHttpException('Usuario no encontrado.');
        }

        if ($objetivo->getId() === $llamante->getId()) {
            throw new BadRequestHttpException('No puedes simularte a ti mismo.');
        }

        if (!$objetivo->isEstado()) {
            throw new BadRequestHttpException('El usuario seleccionado no está activo.');
        }

        $token = $this->jwtManager->createFromPayload($objetivo, [
            'impersonatedBy' => [
                'id' => $llamante->getId(),
                'usuario' => $llamante->getUsuario(),
            ],
        ]);

        $this->em->persist(new UsuarioLog($objetivo, $requestIp, 'SIMULACION_INICIO'));
        $this->em->flush();

        return ['token' => $token, 'usuario' => $objetivo];
    }
}
