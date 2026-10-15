<?php

namespace App\EventListener;

use App\Entity\Usuario;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Agrega el claim "sub" (id del usuario) al payload del JWT.
 * lexik ya incluye "username" y "roles" por defecto; falta el subject estándar.
 */
#[AsEventListener(event: 'lexik_jwt_authentication.on_jwt_created')]
class JwtCreatedListener
{
    public function __invoke(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof Usuario) {
            return;
        }

        $payload = $event->getData();
        $payload['sub'] = $user->getId();
        $event->setData($payload);
    }
}
