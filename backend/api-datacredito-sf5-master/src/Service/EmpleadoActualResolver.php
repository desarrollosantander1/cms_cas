<?php

namespace App\Service;

use App\Entity\Gbl\Usuario;
use App\Repository\Gbl\EmpleadoRepository;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Traduce el usuario autenticado (del JWT) a su empleado_id — el valor que
 * ahora guardan las FK de periodo_cierre / reclasificacion / plano_generado
 * (antes guardaban usuario_id directamente).
 *
 * Todo usuario debería tener su empleado 1:1 (lo crea API users sf4-1 al dar
 * de alta el usuario, y la migración inicial hizo backfill de los
 * preexistentes); si falta, es un estado inconsistente y se corta con 409
 * en vez de escribir una FK nula/incorrecta.
 */
class EmpleadoActualResolver
{
    public function __construct(private readonly EmpleadoRepository $empleados)
    {
    }

    public function resolver(Usuario $usuario): int
    {
        $empleado = $this->empleados->findOneByUsuario($usuario);
        if ($empleado === null) {
            throw new ConflictHttpException(sprintf(
                'El usuario "%s" no tiene un empleado asociado.',
                $usuario->getUserIdentifier(),
            ));
        }

        return $empleado->getId();
    }
}
