<?php

namespace App\Repository\Gbl;

use App\Entity\Gbl\Producto;
use App\Entity\Gbl\ProductoCalidad;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductoCalidad>
 */
class ProductoCalidadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductoCalidad::class);
    }

    /**
     * Fila de calidad de tipo "GARANTIA" de un producto — único consumidor:
     * ProductoGarantiaService. Fila plana (no entidad): solo hace falta
     * `valor` y el código/descripción de la unidad de medida.
     *
     * @return array{valor: string, unidad_codigo: ?string, unidad_descripcion: ?string}|null
     */
    public function buscarGarantia(Producto $producto): ?array
    {
        $sql =
            "SELECT pc.valor, um.codigo AS unidad_codigo, um.descripcion AS unidad_descripcion
            FROM gbl.producto_calidad pc
            JOIN gbl.producto_calidad_tipo ct ON ct.id = pc.calidad_tipo_id
            LEFT JOIN gbl.unidad_medida um ON um.id = pc.unidad_medida_id
            WHERE pc.producto_id = :productoId
              AND pc.estado = true
              AND ct.descripcion = 'GARANTIA'
            LIMIT 1";

        $fila = $this->getEntityManager()->getConnection()->fetchAssociative($sql, ['productoId' => $producto->getId()]);

        return $fila === false ? null : $fila;
    }
}
