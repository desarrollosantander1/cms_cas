<?php

namespace App\Service;

use App\Entity\MenuGrupo;
use App\Repository\MenuGrupoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Reglas de negocio de menu_grupo: nombre único, campos requeridos,
 * persistencia. El controller solo traduce HTTP <-> este servicio.
 */
class MenuGrupoService
{
    public function __construct(
        private readonly MenuGrupoRepository $menuGrupos,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function buscar(int $id): MenuGrupo
    {
        $grupo = $this->menuGrupos->find($id);
        if ($grupo === null) {
            throw new NotFoundHttpException('Grupo de menú no encontrado.');
        }

        return $grupo;
    }

    /** @return MenuGrupo[] */
    public function listar(): array
    {
        return $this->menuGrupos->findAllOrdenado();
    }

    public function crear(array $datos): MenuGrupo
    {
        $grupo = trim((string) ($datos['grupo'] ?? ''));
        $icono = trim((string) ($datos['icono'] ?? ''));
        $this->validarCampos($grupo, $icono);
        $this->validarNombreDisponible($grupo);

        $menuGrupo = new MenuGrupo();
        $menuGrupo->setGrupo($grupo);
        $menuGrupo->setIcono($icono);
        $menuGrupo->setOrden(isset($datos['orden']) ? (int) $datos['orden'] : $this->menuGrupos->proximoOrden());

        $this->em->persist($menuGrupo);
        $this->em->flush();

        return $menuGrupo;
    }

    public function actualizar(MenuGrupo $menuGrupo, array $datos): MenuGrupo
    {
        if (isset($datos['grupo'])) {
            $grupo = trim((string) $datos['grupo']);
            $this->validarCampos($grupo, $menuGrupo->getIcono());
            $this->validarNombreDisponible($grupo, $menuGrupo);
            $menuGrupo->setGrupo($grupo);
        }
        if (isset($datos['icono'])) {
            $icono = trim((string) $datos['icono']);
            $this->validarCampos($menuGrupo->getGrupo(), $icono);
            $menuGrupo->setIcono($icono);
        }
        if (isset($datos['orden'])) {
            $menuGrupo->setOrden((int) $datos['orden']);
        }

        $this->em->flush();

        return $menuGrupo;
    }

    public function activar(MenuGrupo $menuGrupo): void
    {
        $menuGrupo->setEstado(true);
        $this->em->flush();
    }

    public function desactivar(MenuGrupo $menuGrupo): void
    {
        $menuGrupo->setEstado(false);
        $this->em->flush();
    }

    /**
     * Borrado físico: elimina el grupo y, en cascada a nivel de base de
     * datos (menu.menu_grupo_id ON DELETE CASCADE -> menu.menu_padre_id
     * ON DELETE CASCADE -> menu_role_permiso.menu_id ON DELETE CASCADE),
     * todos sus menús, submenús y las relaciones de rol asociadas a esos
     * menús. Operación irreversible — el controller exige confirmación
     * explícita del usuario antes de llegar acá.
     */
    public function eliminar(MenuGrupo $menuGrupo): void
    {
        $this->em->remove($menuGrupo);
        $this->em->flush();
    }

    private function validarCampos(string $grupo, string $icono): void
    {
        if ($grupo === '') {
            throw new BadRequestHttpException('El campo "grupo" es requerido.');
        }
        if ($icono === '') {
            throw new BadRequestHttpException('El campo "icono" es requerido.');
        }
    }

    private function validarNombreDisponible(string $grupo, ?MenuGrupo $actual = null): void
    {
        $existente = $this->menuGrupos->findOneByGrupo($grupo);
        if ($existente !== null && $existente->getId() !== $actual?->getId()) {
            throw new ConflictHttpException('Ya existe un grupo de menú con ese nombre.');
        }
    }
}
