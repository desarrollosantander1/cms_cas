<?php

namespace App\Security;

use App\Entity\Usuario;
use App\Repository\UsuarioRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UsuarioProvider implements UserProviderInterface
{
    public function __construct(private readonly UsuarioRepository $usuarios)
    {
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
}
