<?php

namespace App\Repository;

use App\Entity\MenuGrupo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MenuGrupo>
 */
class MenuGrupoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MenuGrupo::class);
    }

    public function findOneByGrupo(string $grupo): ?MenuGrupo
    {
        return $this->findOneBy(['grupo' => $grupo]);
    }

    /** @return MenuGrupo[] */
    public function findAllOrdenado(): array
    {
        return $this->findBy([], ['orden' => 'ASC', 'id' => 'ASC']);
    }

    /** Siguiente posición libre, para que un grupo nuevo se agregue al final por defecto. */
    public function proximoOrden(): int
    {
        $maximo = $this->createQueryBuilder('g')
            ->select('MAX(g.orden)')
            ->getQuery()
            ->getSingleScalarResult();

        return $maximo === null ? 0 : ((int) $maximo + 1);
    }
}
