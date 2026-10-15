<?php

namespace App\Security\Voter;

use App\Entity\Gbl\Usuario;
use App\Service\PermisosEfectivosService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Autoriza #[IsGranted('ROLE_USUARIO_CREAR')] contra la jerarquía
 * role_permiso (asignado directo o heredado de un ancestro asignado — ver
 * PermisosEfectivosService), no contra Usuario::getRoles() (que siempre es
 * ['ROLE_USER'], el marcador genérico de "está autenticado").
 *
 * El RoleVoter nativo de Symfony también intenta votar estos atributos
 * (empiezan con "ROLE_") y siempre los deniega, porque no están en
 * getRoles() — inofensivo: la estrategia por defecto es "affirmative"
 * (alcanza con que un voter conceda).
 */
class PermisoVoter extends Voter
{
    private const ROLES_NATIVOS = ['ROLE_USER'];

    public function __construct(private readonly PermisosEfectivosService $permisos)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return str_starts_with($attribute, 'ROLE_') && !in_array($attribute, self::ROLES_NATIVOS, true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $usuario = $token->getUser();

        if (!$usuario instanceof Usuario) {
            return false;
        }

        return $this->permisos->tiene($usuario, $attribute);
    }
}
