<?php

namespace App\Repository;

use App\Entity\Gbl\Cliente;
use App\Entity\Gbl\Factura;
use App\Entity\PeriodoCierre;
use App\Entity\Reclasificacion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reclasificacion>
 */
class ReclasificacionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reclasificacion::class);
    }

    public function findOneByFactura(Factura $factura): ?Reclasificacion
    {
        return $this->findOneBy(['factura' => $factura]);
    }

    /**
     * Facturas de este cliente reclasificadas "desde" un período en o antes
     * de $hasta — la fuente de la exclusión del flujo normal (ver
     * PeriodoFacturaService::candidatosParaCliente()). Si una factura tiene
     * más de una fila (no debería, pero por robustez) se queda la más reciente.
     *
     * @return array<int, Reclasificacion> factura_id => Reclasificacion
     */
    public function mapaPorFacturaId(Cliente $cliente, \DateTimeImmutable $hasta): array
    {
        $filas = $this->createQueryBuilder('r')
            ->join('r.factura', 'f')
            ->join('f.clienteSucursal', 'cs')
            ->join('r.periodoCierre', 'p')
            ->andWhere('cs.cliente = :cliente')
            ->andWhere('p.fechaReporte <= :hasta')
            ->setParameter('cliente', $cliente)
            ->setParameter('hasta', $hasta)
            ->orderBy('p.fechaReporte', 'ASC')
            ->getQuery()
            ->getResult();

        $mapa = [];
        foreach ($filas as $fila) {
            $mapa[$fila->getFactura()->getId()] = $fila; // la última en orden ASC gana = la más reciente
        }

        return $mapa;
    }

    /** @return array{reclasificacion: Reclasificacion, creada: bool} */
    public function crearSiNoExiste(Factura $factura, PeriodoCierre $periodoCierre, int $empleadoId, ?string $motivo = null): array
    {
        $existente = $this->findOneByFactura($factura);
        if ($existente !== null) {
            return ['reclasificacion' => $existente, 'creada' => false];
        }

        $nueva = new Reclasificacion($factura, $periodoCierre, $empleadoId, $motivo);
        $this->getEntityManager()->persist($nueva);

        return ['reclasificacion' => $nueva, 'creada' => true];
    }
}
