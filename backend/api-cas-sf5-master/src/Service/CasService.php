<?php

namespace App\Service;

use App\Entity\Cas;
use App\Repository\CasRepository;
use App\Repository\Gbl\ClienteSucursalRepository;
use App\Repository\Gbl\PaisDptoCiudadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Reglas de negocio de gestión de CAS (Tab "CAS" del prompt original): un
 * CAS puede o no estar asociado a un cliente/sucursal existente. `ciudadId`
 * y `nit` solo aplican sin sucursal (con sucursal, el NIT y la ciudad los
 * define el cliente/sucursal, vía CasRepository::listar() + COALESCE en el
 * controller). `nombre`/`direccion`/`telefono` siempre son editables —con o
 * sin sucursal— para permitir que el CAS registre sus propios datos aunque
 * exista sucursal asociada; en blanco, el listado sigue heredando por
 * COALESCE. Sin sucursal, `nombre` es obligatorio (no hay de dónde heredar).
 * El controller solo traduce HTTP <-> este servicio.
 */
class CasService
{
    private const CARTA_CAMBIO_VENCE_DEFECTO = 365;

    public function __construct(
        private readonly CasRepository $casRepository,
        private readonly ClienteSucursalRepository $clienteSucursales,
        private readonly PaisDptoCiudadRepository $ciudades,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function buscar(int $id): Cas
    {
        $cas = $this->casRepository->find($id);
        if ($cas === null) {
            throw new NotFoundHttpException('Centro de servicio no encontrado.');
        }

        return $cas;
    }

    /** @return Cas[] */
    public function listar(): array
    {
        return $this->casRepository->listar();
    }

    public function crear(array $datos): Cas
    {
        $cas = new Cas();
        $cas->setCartaCambioVence(self::CARTA_CAMBIO_VENCE_DEFECTO);
        $this->aplicarDatos($cas, $datos);

        $this->em->persist($cas);
        $this->em->flush();

        return $cas;
    }

    public function actualizar(Cas $cas, array $datos): Cas
    {
        $this->aplicarDatos($cas, $datos);
        $this->em->flush();

        return $cas;
    }

    private function aplicarDatos(Cas $cas, array $datos): void
    {
        if (array_key_exists('clienteSucursalId', $datos) && $datos['clienteSucursalId'] !== null) {
            $sucursal = $this->clienteSucursales->find((int) $datos['clienteSucursalId']);
            if ($sucursal === null) {
                throw new NotFoundHttpException('Sucursal no encontrada.');
            }
            if ($this->casRepository->existePorClienteSucursal($sucursal->getId(), $cas->getId())) {
                throw new ConflictHttpException('Esa sucursal ya está asociada a otro CAS.');
            }
            $cas->setClienteSucursal($sucursal);
            $cas->setCiudad(null);
        } elseif (array_key_exists('clienteSucursalId', $datos)) {
            $cas->setClienteSucursal(null);
        }

        if ($cas->getClienteSucursal() === null && array_key_exists('ciudadId', $datos) && $datos['ciudadId'] !== null) {
            $ciudad = $this->ciudades->find((int) $datos['ciudadId']);
            if ($ciudad === null) {
                throw new NotFoundHttpException('Ciudad no encontrada.');
            }
            $cas->setCiudad($ciudad);
        } elseif ($cas->getClienteSucursal() === null && array_key_exists('ciudadId', $datos)) {
            $cas->setCiudad(null);
        }

        if (array_key_exists('nit', $datos) && $datos['nit'] !== null && trim((string) $datos['nit']) !== '') {
            $nit = trim((string) $datos['nit']);
            if ($this->casRepository->existePorNit($nit, $cas->getId())) {
                throw new ConflictHttpException('Ya existe un CAS con ese NIT.');
            }
            $cas->setNit($nit);
        } elseif (array_key_exists('nit', $datos)) {
            $cas->setNit(null);
        }

        if (array_key_exists('nombre', $datos)) {
            $nombre = $datos['nombre'] !== null ? trim((string) $datos['nombre']) : '';
            $cas->setNombre($nombre !== '' ? $nombre : null);
        }
        if (array_key_exists('direccion', $datos)) {
            $cas->setDireccion($datos['direccion']);
        }
        if (array_key_exists('telefono', $datos)) {
            $cas->setTelefono($datos['telefono']);
        }
        if (array_key_exists('cartaCambioVence', $datos) && $datos['cartaCambioVence'] !== null) {
            $cas->setCartaCambioVence((int) $datos['cartaCambioVence']);
        }
        if (array_key_exists('estado', $datos) && $datos['estado'] !== null) {
            $cas->setEstado((bool) $datos['estado']);
        }

        // Se valida al final, sobre el estado ya resuelto de la entidad — no
        // sobre la presencia de la clave "nombre" en el payload: el frontend
        // siempre envía esa clave (aunque sea null cuando el campo queda en
        // blanco), así que validar por presencia de la clave nunca disparaba
        // este error. Sin sucursal no hay de dónde heredar nombre por
        // COALESCE, así que el nombre manual es obligatorio en ese caso.
        if ($cas->getClienteSucursal() === null && ($cas->getNombre() === null || trim($cas->getNombre()) === '')) {
            throw new BadRequestHttpException('El nombre es requerido cuando el CAS no tiene sucursal asociada.');
        }
    }
}
