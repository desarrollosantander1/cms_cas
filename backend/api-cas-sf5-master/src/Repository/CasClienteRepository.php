<?php

namespace App\Repository;

use App\Entity\Cas;
use App\Entity\CasCliente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CasCliente>
 */
class CasClienteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CasCliente::class);
    }

    /**
     * Un mismo número de identidad puede existir como cas_cliente en varios
     * CAS distintos (persona que compra en más de un centro de servicio) —
     * por eso la búsqueda siempre va acotada al CAS, nunca solo por cédula.
     * Localiza el id por SQL nativo y delega en find() (lookup por PK, no
     * DQL) para devolver la entidad gestionada por el EntityManager.
     */
    public function buscarPorCasYCedula(Cas $cas, string $cedula): ?CasCliente
    {
        $id = $this->getEntityManager()->getConnection()->fetchOne(
            'SELECT id FROM cas.cas_cliente WHERE cas_id = :casId AND numero_identidad = :cedula LIMIT 1',
            ['casId' => $cas->getId(), 'cedula' => $cedula],
        );

        return $id === false ? null : $this->find((int) $id);
    }
}
