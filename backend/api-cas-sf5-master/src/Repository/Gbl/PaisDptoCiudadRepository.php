<?php

namespace App\Repository\Gbl;

use App\Entity\Gbl\PaisDptoCiudad;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PaisDptoCiudad>
 */
class PaisDptoCiudadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaisDptoCiudad::class);
    }

    public function findOneByNetsuiteId(string $netsuiteId): ?PaisDptoCiudad
    {
        return $this->findOneBy(['netsuiteId' => $netsuiteId]);
    }

    /**
     * Catálogo completo para el selector de ciudad del modal de CAS (se usa
     * cuando el centro de servicio no tiene sucursal asociada).
     *
     * @return PaisDptoCiudad[]
     */
    public function listar(): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.paisDpto', 'd')
            ->addSelect('d')
            ->join('d.pais', 'p')
            ->addSelect('p')
            ->orderBy('p.nombre', 'ASC')
            ->addOrderBy('d.nombre', 'ASC')
            ->addOrderBy('c.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
