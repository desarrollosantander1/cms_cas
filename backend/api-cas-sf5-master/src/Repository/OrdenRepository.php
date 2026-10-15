<?php

namespace App\Repository;

use App\Entity\Cas;
use App\Entity\Orden;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Orden>
 */
class OrdenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Orden::class);
    }

    /**
     * Serializa (dentro de la transacción actual) a cualquier otra
     * conexión que intente lo mismo para este CAS — el lock se libera solo
     * al terminar la transacción. Debe llamarse ANTES de siguienteNumero()
     * en la misma transacción de OrdenService::crear(); UNIQUE(cas_id,
     * numero) (ver Version20260929170000) es el respaldo si de todas formas
     * llegaran a chocar dos números.
     */
    public function bloquearNumeracion(Cas $cas): void
    {
        $this->getEntityManager()->getConnection()->fetchOne('SELECT pg_advisory_xact_lock(:casId)', ['casId' => $cas->getId()]);
    }

    /** Siguiente número de orden dentro de ESTE CAS (numeración secuencial por CAS, no global). Ver bloquearNumeracion(). */
    public function siguienteNumero(Cas $cas): int
    {
        $siguiente = $this->getEntityManager()->getConnection()->fetchOne(
            'SELECT COALESCE(MAX(numero), 0) + 1 FROM cas.orden WHERE cas_id = :casId',
            ['casId' => $cas->getId()],
        );

        return (int) $siguiente;
    }

    /**
     * Listado de órdenes de un CAS para la tabla de /cas/ordenes: cruza 4 tablas (orden +
     * cas_cliente + gbl.producto + orden_estado) solo para mostrar sus campos "planos" (nombre
     * del cliente ya concatenado, etc.) — SQL nativo en vez de QueryBuilder/DQL porque no hace
     * falta hidratar entidades completas, un array asociativo por fila alcanza y es más barato.
     *
     * @return array<int, array{id: int, fecha_orden: string, cliente: string, producto: string, estado: string}>
     */
    public function listarPorCas(Cas $cas): array
    {
        return $this->getEntityManager()->getConnection()->fetchAllAssociative(
            <<<'SQL'
                SELECT
                    o.id,
                    o.creado_en AS fecha_orden,
                    cc.nombre || ' ' || cc.apellido AS cliente,
                    p.item || '-' || p.descripcion AS producto,
                    oe.descripcion AS estado
                FROM cas.orden o
                JOIN cas.cas_cliente cc ON cc.id = o.cas_cliente_id
                JOIN gbl.producto p ON p.id = o.producto_id
                JOIN cas.orden_estado oe ON oe.id = o.orden_estado_id
                WHERE o.cas_id = :casId
                ORDER BY o.id DESC
                SQL,
            ['casId' => $cas->getId()],
        );
    }
}
