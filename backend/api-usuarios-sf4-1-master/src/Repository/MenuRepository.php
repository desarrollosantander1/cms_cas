<?php

namespace App\Repository;

use App\Entity\Menu;
use App\Entity\MenuGrupo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Menu>
 */
class MenuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Menu::class);
    }

    public function findOneByMenu(string $menu): ?Menu
    {
        return $this->findOneBy(['menu' => $menu]);
    }

    /**
     * true si ya existe otro menú con la misma etiqueta entre "hermanos"
     * (mismo menu_padre_id si tiene padre, o mismo menu_grupo_id entre los
     * de primer nivel si no lo tiene) — evita entradas duplicadas confusas
     * en un mismo nivel del árbol.
     */
    public function existeHermano(string $menu, ?Menu $padre, ?MenuGrupo $grupo, ?Menu $actual = null): bool
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('LOWER(m.menu) = LOWER(:menu)')
            ->setParameter('menu', $menu);

        if ($padre !== null) {
            $qb->andWhere('m.menuPadre = :padre')->setParameter('padre', $padre);
        } else {
            $qb->andWhere('m.menuPadre IS NULL')
                ->andWhere('m.menuGrupo = :grupo')->setParameter('grupo', $grupo);
        }

        if ($actual !== null) {
            $qb->andWhere('m.id != :actualId')->setParameter('actualId', $actual->getId());
        }

        return $qb->setMaxResults(1)->getQuery()->getOneOrNullResult() !== null;
    }

    /**
     * Todo el árbol, con su grupo, para construirlo en memoria. Ordenado por
     * "orden" — como MenuTreeService agrupa por menu_grupo_id/menu_padre_id
     * preservando el orden de llegada, esto alcanza para ordenar tanto los
     * grupos de primer nivel entre sí como los hijos de cada padre entre sí.
     */
    public function findAllOrdenado(): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.menuGrupo', 'g')->addSelect('g')
            ->orderBy('m.orden', 'ASC')
            ->addOrderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Siguiente posición libre entre "hermanos" (mismo padre, o mismo grupo
     * si es de primer nivel), para que un menú nuevo se agregue al final.
     */
    public function proximoOrden(?Menu $padre, ?MenuGrupo $grupo): int
    {
        $qb = $this->createQueryBuilder('m')->select('MAX(m.orden)');

        if ($padre !== null) {
            $qb->andWhere('m.menuPadre = :padre')->setParameter('padre', $padre);
        } else {
            $qb->andWhere('m.menuPadre IS NULL')
                ->andWhere('m.menuGrupo = :grupo')->setParameter('grupo', $grupo);
        }

        $maximo = $qb->getQuery()->getSingleScalarResult();

        return $maximo === null ? 0 : ((int) $maximo + 1);
    }
}
