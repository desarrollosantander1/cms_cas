<?php

namespace App\Repository\Gbl;

use App\Entity\Gbl\Producto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Producto>
 */
class ProductoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Producto::class);
    }

    /**
     * Búsqueda para el selector de producto de la creación de orden: por
     * código de item o por descripción, solo productos activos. Devuelve
     * filas planas (no entidades) — es todo lo que necesita el autocomplete
     * del frontend, ver ProductoController::buscar().
     *
     * @return array<int, array{id: int, item: string, descripcion: ?string}>
     */
    public function buscarActivos(string $q, int $limite = 20): array
    {
        $sql =
            'SELECT id, item, descripcion
            FROM gbl.producto
            WHERE estado = true
              AND (LOWER(item) LIKE :termino OR LOWER(descripcion) LIKE :termino)
            ORDER BY item ASC
            LIMIT :limite';

        return $this->getEntityManager()->getConnection()->fetchAllAssociative($sql, [
            'termino' => '%'.mb_strtolower(trim($q)).'%',
            'limite' => $limite,
        ]);
    }
}
