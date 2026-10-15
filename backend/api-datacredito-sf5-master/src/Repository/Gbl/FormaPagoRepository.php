<?php

namespace App\Repository\Gbl;

use App\Entity\Gbl\FormaPago;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormaPago>
 */
class FormaPagoRepository extends ServiceEntityRepository
{
    /** Id sembrado por migración (Version20260901160000) — ver FacturaRepository::findByCliente(). */
    public const ID_CREDITO = 2;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormaPago::class);
    }

    public function findOneByIdNetsuite(string $idNetsuite): ?FormaPago
    {
        return $this->findOneBy(['idNetsuite' => $idNetsuite]);
    }
}
