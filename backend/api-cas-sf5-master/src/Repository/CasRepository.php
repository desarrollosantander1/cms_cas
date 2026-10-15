<?php

namespace App\Repository;

use App\Entity\Cas;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cas>
 */
class CasRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cas::class);
    }

    /**
     * Listado completo para la Tab "CAS" — trae de una vez la sucursal, su
     * cliente y la ciudad manual, para poder calcular los COALESCE de
     * nit/nombre/dirección/teléfono en el servicio sin N+1 queries.
     *
     * @return Cas[]
     */
    public function listar(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.clienteSucursal', 'cs')
            ->addSelect('cs')
            ->leftJoin('cs.cliente', 'cl')
            ->addSelect('cl')
            ->leftJoin('c.ciudad', 'ciu')
            ->addSelect('ciu')
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function existePorClienteSucursal(int $clienteSucursalId, ?int $excluirCasId = null): bool
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.clienteSucursal = :sucursalId')
            ->setParameter('sucursalId', $clienteSucursalId);

        if ($excluirCasId !== null) {
            $qb->andWhere('c.id != :excluirId')->setParameter('excluirId', $excluirCasId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function existePorNit(string $nit, ?int $excluirCasId = null): bool
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.nit = :nit')
            ->setParameter('nit', $nit);

        if ($excluirCasId !== null) {
            $qb->andWhere('c.id != :excluirId')->setParameter('excluirId', $excluirCasId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
