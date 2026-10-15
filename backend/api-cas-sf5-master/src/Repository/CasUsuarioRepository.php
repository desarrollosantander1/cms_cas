<?php

namespace App\Repository;

use App\Entity\Cas;
use App\Entity\CasUsuario;
use App\Entity\Gbl\Usuario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CasUsuario>
 */
class CasUsuarioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CasUsuario::class);
    }

    public function existe(Cas $cas, Usuario $usuario): bool
    {
        return $this->buscarRelacion($cas, $usuario) !== null;
    }

    /** La relación puntual cas_usuario para este CAS y este usuario (si existe) — ver CasUsuarioService::actualizarUsuario(). */
    public function buscarRelacion(Cas $cas, Usuario $usuario): ?CasUsuario
    {
        return $this->findOneBy(['cas' => $cas, 'usuario' => $usuario]);
    }

    /** Usuarios de un CAS — para el modal de la Tab "CAS" (punto 92 del prompt). @return CasUsuario[] */
    public function listarPorCas(Cas $cas): array
    {
        return $this->createQueryBuilder('cu')
            ->addSelect('u')
            ->join('cu.usuario', 'u')
            ->andWhere('cu.cas = :cas')
            ->setParameter('cas', $cas)
            ->orderBy('cu.creadoEn', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** Un usuario puede estar en varios CAS: sus asociaciones actuales, para el modal de búsqueda por documento. @return CasUsuario[] */
    public function listarPorUsuario(Usuario $usuario): array
    {
        return $this->createQueryBuilder('cu')
            ->addSelect('c')
            ->join('cu.cas', 'c')
            ->leftJoin('c.clienteSucursal', 'cs')
            ->addSelect('cs')
            ->leftJoin('cs.cliente', 'cl')
            ->addSelect('cl')
            ->andWhere('cu.usuario = :usuario')
            ->setParameter('usuario', $usuario)
            ->getQuery()
            ->getResult();
    }

    /** Tab "Usuarios" — una fila por relación cas_usuario (un usuario en varios CAS aparece varias veces). @return CasUsuario[] */
    public function listarTodos(): array
    {
        return $this->createQueryBuilder('cu')
            ->addSelect('u', 'c', 'cs', 'cl')
            ->join('cu.usuario', 'u')
            ->join('cu.cas', 'c')
            ->leftJoin('c.clienteSucursal', 'cs')
            ->leftJoin('cs.cliente', 'cl')
            ->orderBy('cu.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * CAS activos (cas.cas_usuario.estado = true) de un usuario — para el
     * selector de centro de servicio de "Órdenes" (ver MisCasController).
     * SQL nativo de Postgres en vez de DQL: punto de partida acordado para
     * los repositorios de este dominio a partir de ahora.
     *
     * Orden por cu.creado_en ASC: el primero de la lista es el CAS al que
     * el usuario se asoció primero — el frontend lo usa como selección por
     * defecto cuando no hay nada guardado en localStorage.
     *
     * @return array<int, array{id: int, nombre: ?string, nit: ?string}>
     */
    public function misCasActivos(int $usuarioId): array
    {
        $sql =
            "SELECT c.id,
                  c.id || ' - ' || COALESCE(c.nombre, cs.nombre) AS nombre,
                   COALESCE(c.nit, cl.numero_identidad) AS nit
            FROM cas.cas_usuario cu
            INNER JOIN cas.cas c ON c.id = cu.cas_id
            LEFT JOIN gbl.cliente_sucursal cs ON cs.id = c.cliente_sucursal_id
            LEFT JOIN gbl.cliente cl ON cl.id = cs.cliente_id
            WHERE cu.usuario_id = :usuarioId
              AND cu.estado = true
            ORDER BY cu.creado_en ASC, c.id ASC";

        return $this->getEntityManager()->getConnection()->fetchAllAssociative($sql, ['usuarioId' => $usuarioId]);
    }
}
