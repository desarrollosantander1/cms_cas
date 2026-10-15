<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Este servicio solo habla JSON: convierte las páginas HTML por defecto de
 * Symfony (403 de Voter denegado, 401 de autenticación, 404, 500, etc.) en
 * respuestas JSON consistentes para el frontend.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION)]
class ApiExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $e = $event->getThrowable();

        $status = match (true) {
            $e instanceof AccessDeniedException => 403,
            $e instanceof AuthenticationException => 401,
            $e instanceof HttpExceptionInterface => $e->getStatusCode(),
            default => 500,
        };

        $message = $status === 500 ? 'Error interno del servidor.' : $e->getMessage();

        $event->setResponse(new JsonResponse([
            'message' => $message, 
            'error'   => [
                'type'    => get_class($e),
                'message' => $e->getMessage(),
                'code'    => $e->getCode(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString()
            ]
        ], $status));
    }
}
