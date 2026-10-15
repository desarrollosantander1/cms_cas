<?php

namespace App\Security;

use App\Entity\Usuario;
use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Carga el Usuario autenticable desde gbl.usuario. No expone usuarios inactivos:
 * para el firewall, un usuario con estado=false simplemente no existe.
 */
class UsuarioProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private readonly UsuarioRepository $usuarios,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $usuario = $this->usuarios->findOneByUsuario($identifier);

        if (!$usuario instanceof Usuario || !$usuario->isEstado()) {
            throw new UserNotFoundException(sprintf('Usuario "%s" no encontrado.', $identifier));
        }

        return $usuario;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof Usuario) {
            throw new UnsupportedUserException(sprintf('Usuarios de tipo "%s" no son soportados.', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return Usuario::class === $class || is_subclass_of($class, Usuario::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface|UserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Usuario) {
            return;
        }

        $user->setPassword($newHashedPassword);
        $this->em->flush();
    }
}
