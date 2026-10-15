<?php

namespace App\Repository\Gbl;

use App\Entity\Gbl\Cliente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cliente>
 */
class ClienteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cliente::class);
    }

    public function findOneByNetsuiteId(string $netsuiteId): ?Cliente
    {
        return $this->findOneBy(['netsuiteId' => $netsuiteId]);
    }

    /** "tercero" en el contrato del frontend — el NIT/cédula, no un id interno. */
    public function findOneByNumeroIdentidad(string $numeroIdentidad): ?Cliente
    {
        return $this->findOneBy(['numeroIdentidad' => $numeroIdentidad]);
    }

    /**
     * Búsqueda para el selector de cliente del modal de creación/edición de
     * CAS: por NIT/cédula (numeroIdentidad) o por nombre (razonSocial /
     * nombre comercial).
     *
     * @return Cliente[]
     */
    public function buscar(string $q, int $limite = 20): array
    {
        $termino = '%'.mb_strtolower(trim($q)).'%';

        return $this->createQueryBuilder('c')
            ->andWhere('LOWER(c.numeroIdentidad) LIKE :t OR LOWER(c.razonSocial) LIKE :t OR LOWER(c.nombreComercial) LIKE :t')
            ->setParameter('t', $termino)
            ->orderBy('c.razonSocial', 'ASC')
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();
    }
}
