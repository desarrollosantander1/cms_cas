<?php

namespace App\Service;

use App\Entity\RolePermiso;
use App\Repository\RolePermisoRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Reglas de negocio de role_permiso: la jerarquía se limita a tres niveles
 * (rol padre -> rol hijo -> rol sub-hijo), un rol no puede quedar como su
 * propio ancestro, y el nombre es único (la base ya lo exige; acá se
 * valida antes para dar un mensaje claro). El controller solo traduce
 * HTTP <-> este servicio.
 */
class RolePermisoService
{
    private const NIVEL_MAXIMO = 3;

    public function __construct(
        private readonly RolePermisoRepository $roles,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function buscar(int $id): RolePermiso
    {
        $rol = $this->roles->find($id);
        if ($rol === null) {
            throw new NotFoundHttpException('Rol no encontrado.');
        }

        return $rol;
    }

    /** @return RolePermiso[] */
    public function listar(): array
    {
        return $this->roles->findBy([], ['id' => 'ASC']);
    }

    public function crear(array $datos): RolePermiso
    {
        $nombre = trim((string) ($datos['role'] ?? ''));
        if ($nombre === '') {
            throw new BadRequestHttpException('El campo "role" es requerido.');
        }

        $padre = $this->resolverPadre($datos['rolePadreId'] ?? null);
        $this->validarNivelMaximo($padre);
        $this->validarNombreDisponible($nombre);

        $rol = new RolePermiso();
        $rol->setRole($nombre);
        $rol->setRolePadre($padre);

        $this->guardar($rol);

        return $rol;
    }

    public function actualizar(RolePermiso $rol, array $datos): RolePermiso
    {
        if (isset($datos['role'])) {
            $nombre = trim((string) $datos['role']);
            if ($nombre === '') {
                throw new BadRequestHttpException('El campo "role" es requerido.');
            }
            $this->validarNombreDisponible($nombre, $rol);
            $rol->setRole($nombre);
        }

        if (array_key_exists('rolePadreId', $datos)) {
            $padre = $this->resolverPadre($datos['rolePadreId']);
            $this->validarSinCiclo($rol, $padre);
            $this->validarNivelMaximo($padre, $rol);
            $rol->setRolePadre($padre);
        }

        $this->em->flush();

        return $rol;
    }

    public function activar(RolePermiso $rol): void
    {
        $rol->setEstado(true);
        $this->em->flush();
    }

    public function desactivar(RolePermiso $rol): void
    {
        $rol->setEstado(false);
        $this->em->flush();
    }

    private function resolverPadre(mixed $rolePadreId): ?RolePermiso
    {
        if ($rolePadreId === null || $rolePadreId === '') {
            return null;
        }

        $padre = $this->roles->find((int) $rolePadreId);
        if ($padre === null) {
            throw new NotFoundHttpException('El rol padre indicado no existe.');
        }

        return $padre;
    }

    /**
     * La jerarquía se limita a tres niveles. Al crear, el nuevo rol queda
     * un nivel por debajo de su padre (o en el nivel 1 si no tiene). Al
     * mover un rol existente, además hay que revisar cuántos niveles
     * cuelgan ya debajo de él: moverlo más abajo no puede dejar a sus
     * propios descendientes pasado el nivel 3.
     */
    private function validarNivelMaximo(?RolePermiso $padre, ?RolePermiso $rol = null): void
    {
        $nivelResultante = ($padre?->nivel() ?? 0) + 1;
        if ($nivelResultante > self::NIVEL_MAXIMO) {
            throw new BadRequestHttpException('No se pueden crear roles de más de tres niveles.');
        }

        if ($rol !== null && $nivelResultante + $this->alturaSubarbol($rol) > self::NIVEL_MAXIMO) {
            throw new BadRequestHttpException('Este rol tiene roles hijos propios que quedarían en más de tres niveles con ese padre.');
        }
    }

    /** Niveles que cuelgan por debajo de $rol (0 si no tiene hijos). */
    private function alturaSubarbol(RolePermiso $rol): int
    {
        $maxima = 0;
        foreach ($rol->getHijos() as $hijo) {
            $maxima = max($maxima, 1 + $this->alturaSubarbol($hijo));
        }

        return $maxima;
    }

    /** Un rol no puede terminar teniendo como padre a uno de sus propios descendientes (ni a sí mismo). */
    private function validarSinCiclo(RolePermiso $rol, ?RolePermiso $nuevoPadre): void
    {
        $candidato = $nuevoPadre;
        while ($candidato !== null) {
            if ($candidato === $rol) {
                throw new BadRequestHttpException('Un rol no puede ser su propio ancestro.');
            }
            $candidato = $candidato->getRolePadre();
        }
    }

    private function validarNombreDisponible(string $nombre, ?RolePermiso $actual = null): void
    {
        $existente = $this->roles->findOneByRole($nombre);
        if ($existente !== null && $existente->getId() !== $actual?->getId()) {
            throw new ConflictHttpException('Ya existe un rol con ese nombre.');
        }
    }

    private function guardar(RolePermiso $rol): void
    {
        try {
            $this->em->persist($rol);
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            throw new ConflictHttpException('Ya existe un rol con ese nombre.');
        }
    }
}
