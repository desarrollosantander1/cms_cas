<?php

namespace App\EventListener;

use App\Entity\Usuario;
use App\Entity\UsuarioLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * Lleva el contador usuario.fallos (+1 en fallo, reset en éxito, sin umbral
 * de bloqueo todavía) y deja constancia en gbl.usuario_log de cada intento.
 */
class LoginAttemptListener
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[AsEventListener(event: LoginSuccessEvent::class)]
    public function onSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof Usuario) {
            return;
        }

        $user->resetearFallos();
        $this->em->persist(new UsuarioLog($user, $this->clientIp(), 'EXITOSO'));
        $this->em->flush();
    }

    #[AsEventListener(event: LoginFailureEvent::class)]
    public function onFailure(LoginFailureEvent $event): void
    {
        $user = $event->getPassport()?->getUser();
        if (!$user instanceof Usuario) {
            return;
        }

        $user->registrarLoginFallido();
        $this->em->persist(new UsuarioLog($user, $this->clientIp(), 'FALLIDO'));
        $this->em->flush();
    }

    private function clientIp(): ?string
    {
        return $this->requestStack->getCurrentRequest()?->getClientIp();
    }
}
