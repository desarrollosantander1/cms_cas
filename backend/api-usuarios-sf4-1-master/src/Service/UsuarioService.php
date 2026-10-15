<?php

namespace App\Service;

use App\Entity\Empleado;
use App\Entity\RolePermiso;
use App\Entity\Usuario;
use App\Entity\UsuarioRolePermiso;
use App\Repository\RolePermisoRepository;
use App\Repository\UsuarioRepository;
use App\Repository\UsuarioRolePermisoRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Reglas de negocio de gestión de usuarios: validación de campos, unicidad
 * de documento/usuario, hashing de contraseña, persistencia y asignación de
 * roles. El controller solo traduce HTTP <-> este servicio; las violaciones
 * de reglas se comunican lanzando excepciones HTTP (las captura
 * ApiExceptionListener y las convierte a JSON).
 */
class UsuarioService
{
    private const CAMPOS_REQUERIDOS_CREAR = ['documento', 'nombre', 'apellido', 'usuario', 'email', 'password'];

    public function __construct(
        private readonly UsuarioRepository $usuarios,
        private readonly RolePermisoRepository $rolesPermiso,
        private readonly UsuarioRolePermisoRepository $usuarioRolesPermiso,
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function buscar(int $id): Usuario
    {
        $usuario = $this->usuarios->find($id);
        if ($usuario === null) {
            throw new NotFoundHttpException('Usuario no encontrado.');
        }

        return $usuario;
    }

    public function buscarRol(int $id): RolePermiso
    {
        $rol = $this->rolesPermiso->find($id);
        if ($rol === null) {
            throw new NotFoundHttpException('Rol no encontrado.');
        }

        return $rol;
    }

    /** @return Usuario[] */
    public function listar(string $busqueda): array
    {
        $qb = $this->usuarios->createQueryBuilder('u')->orderBy('u.id', 'DESC');
        if ($busqueda !== '') {
            $qb->andWhere('u.nombre ILIKE :q OR u.apellido ILIKE :q OR u.usuario ILIKE :q OR u.documento ILIKE :q')
                ->setParameter('q', '%' . $busqueda . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /** @return UsuarioRolePermiso[] */
    public function rolesActivos(Usuario $usuario): array
    {
        return $this->usuarioRolesPermiso->asignadosActivos($usuario);
    }

    /** @return Usuario[] activos, sin incluir a quien pregunta — para el selector de "simular usuario" */
    public function activosParaSimular(Usuario $llamante): array
    {
        return $this->usuarios->findActivosExcluyendo($llamante->getId());
    }

    public function crear(array $datos): Usuario
    {
        $this->validarCamposRequeridos($datos);
        $this->validarDocumentoDisponible($datos['documento']);
        $this->validarUsuarioDisponible($datos['usuario']);

        $usuario = new Usuario();
        $usuario->setDocumento($datos['documento']);
        $usuario->setNombre($datos['nombre']);
        $usuario->setApellido($datos['apellido']);
        $usuario->setTelefono($datos['telefono'] ?? null);
        $usuario->setUsuario($datos['usuario']);
        $usuario->setEmail($datos['email']);
        $usuario->setEstado(true);
        $usuario->setPassword($this->passwordHasher->hashPassword($usuario, $datos['password']));

        // Todo usuario nace con su empleado 1:1 (netsuite_empleado_id se completa
        // luego vía el sync). Misma unidad de trabajo — ver guardar().
        $this->guardar($usuario, new Empleado($usuario));

        return $usuario;
    }

    public function actualizar(Usuario $usuario, array $datos): Usuario
    {
        if (isset($datos['documento']) && trim((string) $datos['documento']) !== '') {
            $this->validarDocumentoDisponible($datos['documento'], $usuario);
            $usuario->setDocumento($datos['documento']);
        }
        if (isset($datos['usuario']) && trim((string) $datos['usuario']) !== '') {
            $this->validarUsuarioDisponible($datos['usuario'], $usuario);
            $usuario->setUsuario($datos['usuario']);
        }
        if (isset($datos['nombre'])) {
            $usuario->setNombre($datos['nombre']);
        }
        if (isset($datos['apellido'])) {
            $usuario->setApellido($datos['apellido']);
        }
        if (array_key_exists('telefono', $datos)) {
            $usuario->setTelefono($datos['telefono']);
        }
        if (isset($datos['email'])) {
            $usuario->setEmail($datos['email']);
        }

        $usuario->tocarModificado();
        $this->guardar($usuario);

        return $usuario;
    }

    public function activar(Usuario $usuario): void
    {
        $usuario->setEstado(true);
        $usuario->tocarModificado();
        $this->em->flush();
    }

    public function desactivar(Usuario $usuario): void
    {
        $usuario->setEstado(false);
        $usuario->tocarModificado();
        $this->em->flush();
    }

    public function cambiarPassword(Usuario $usuario, string $password): void
    {
        if (strlen($password) < 8) {
            throw new BadRequestHttpException('La contraseña debe tener al menos 8 caracteres.');
        }

        $usuario->setPassword($this->passwordHasher->hashPassword($usuario, $password));
        $this->em->flush();
    }

    public function asignarRol(Usuario $usuario, RolePermiso $rol): void
    {
        if ($this->usuarioRolesPermiso->buscarAsignacion($usuario, $rol) === null) {
            $this->em->persist(new UsuarioRolePermiso($usuario, $rol));
            $this->em->flush();
        }
    }

    public function quitarRol(Usuario $usuario, RolePermiso $rol): void
    {
        $asignacion = $this->usuarioRolesPermiso->buscarAsignacion($usuario, $rol);
        if ($asignacion !== null) {
            $this->em->remove($asignacion);
            $this->em->flush();
        }
    }

    private function validarCamposRequeridos(array $datos): void
    {
        foreach (self::CAMPOS_REQUERIDOS_CREAR as $campo) {
            if (trim((string) ($datos[$campo] ?? '')) === '') {
                throw new BadRequestHttpException(sprintf('El campo "%s" es requerido.', $campo));
            }
        }
    }

    private function validarDocumentoDisponible(string $documento, ?Usuario $actual = null): void
    {
        $existente = $this->usuarios->findOneByDocumento($documento);
        if ($existente !== null && $existente->getId() !== $actual?->getId()) {
            throw new ConflictHttpException('Ya existe un usuario con ese documento.');
        }
    }

    private function validarUsuarioDisponible(string $usuarioNombre, ?Usuario $actual = null): void
    {
        $existente = $this->usuarios->findOneByUsuario($usuarioNombre);
        if ($existente !== null && $existente->getId() !== $actual?->getId()) {
            throw new ConflictHttpException('Ese nombre de usuario ya está en uso.');
        }
    }

    private function guardar(Usuario $usuario, ?Empleado $empleado = null): void
    {
        try {
            $this->em->persist($usuario);
            if ($empleado !== null) {
                $this->em->persist($empleado);
            }
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            throw new ConflictHttpException('El documento, usuario o email ya están en uso.');
        }
    }
}
