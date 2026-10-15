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
     * Búsqueda para el autocompletar del Generador de Cartas: por NIT/cédula
     * (numeroIdentidad) o por nombre (razonSocial / nombre comercial). NO
     * aplica el gate de findElegibles() — una carta de cobro jurídico no es
     * un reporte a central de riesgo, se le puede enviar a cualquier cliente
     * moroso independientemente de si autorizó el reporte a DataCrédito.
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

    /**
     * Clientes habilitados para reportar: autoriza_reporte=true (gate de
     * consentimiento, reemplaza el CRM_CLIENTE.MEDIO='b' del legacy) y
     * tipoCliente=NACIONAL (DataCrédito no reporta terceros extranjeros —
     * antes tipo_tercero=1, ahora normalizado en gbl.cliente_tipo, ver
     * ClienteTipo). Se filtra por descripción, no por el id del catálogo.
     *
     * @return Cliente[]
     */
    public function findElegibles(): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.tipoCliente', 'tc')
            ->andWhere('c.autorizaReporte = true')
            ->andWhere('tc.descripcion = :nacional')
            ->setParameter('nacional', ClienteTipoRepository::NACIONAL)
            ->orderBy('c.razonSocial', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
