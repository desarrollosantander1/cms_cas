<?php

namespace App\Service;

use App\Entity\Cas;
use App\Entity\CasUsuario;
use App\Entity\Gbl\Usuario;
use App\Entity\Gbl\UsuarioRolePermiso;
use App\Repository\CasUsuarioRepository;
use App\Repository\Gbl\RolePermisoRepository;
use App\Repository\Gbl\UsuarioRepository;
use App\Repository\Gbl\UsuarioRolePermisoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Núcleo de negocio de asociación usuario <-> CAS (puntos 75-98 del encargo
 * original). Un CAS es un establecimiento externo: sus usuarios viven en
 * gbl.usuario igual que cualquier otro usuario de la plataforma (nunca en
 * gbl.empleado, que representa personal interno), y se marcan con ROLE_CAS
 * (gbl.role_permiso, ya existente) además de la relación en cas.cas_usuario.
 */
class CasUsuarioService
{
    /** Campos requeridos al crear un gbl.usuario nuevo — igual que UsuarioService::crear() en API users sf4-1, salvo que aquí nunca se crea un Empleado. */
    private const CAMPOS_REQUERIDOS_USUARIO_NUEVO = ['nombre', 'apellido', 'usuario', 'email', 'password'];
    private const ROLE_CAS = 'ROLE_CAS';

    public function __construct(
        private readonly UsuarioRepository $usuarios,
        private readonly CasUsuarioRepository $casUsuarios,
        private readonly RolePermisoRepository $rolesPermiso,
        private readonly UsuarioRolePermisoRepository $usuarioRolesPermiso,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly PasswordStrengthValidator $passwordStrength,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * Para el buscador del modal de asociación: usuario (si existe) + los
     * CAS a los que ya está asociado (punto 82: "informar si ya está
     * asociado a otros CAS").
     *
     * @return array{usuario: ?Usuario, casUsuarios: CasUsuario[]}
     */
    public function buscarPorDocumento(string $documento): array
    {
        $usuario = $this->usuarios->findOneByDocumento($documento);

        return [
            'usuario' => $usuario,
            'casUsuarios' => $usuario !== null ? $this->casUsuarios->listarPorUsuario($usuario) : [],
        ];
    }

    public function asociar(Cas $cas, array $datos): CasUsuario
    {
        $documento = trim((string) ($datos['documento'] ?? ''));
        if ($documento === '') {
            throw new BadRequestHttpException('El documento es requerido.');
        }

        $usuario = $this->usuarios->findOneByDocumento($documento);

        if ($usuario !== null) {
            $this->actualizarPasswordSiViene($usuario, $datos);
        } else {
            $usuario = $this->crearUsuario($documento, $datos);
        }

        if ($this->casUsuarios->existe($cas, $usuario)) {
            throw new ConflictHttpException('Este usuario ya está asociado a este CAS.');
        }

        $this->asegurarRoleCas($usuario);

        $casUsuario = new CasUsuario($cas, $usuario);
        $this->em->persist($casUsuario);
        $this->em->flush();

        return $casUsuario;
    }

    /**
     * Edición de un usuario ya vinculado a un CAS (punto 92: última columna
     * del listado del modal). `$cas` identifica DE CUÁL relación cas_usuario
     * se trata — necesario porque `estado` ya no es el estado global de
     * gbl.usuario (eso afectaría el acceso del usuario a toda la plataforma,
     * no solo a este CAS) sino el de esa relación puntual: un mismo usuario
     * puede estar activo en un CAS e inactivo en otro.
     */
    public function actualizarUsuario(Cas $cas, int $usuarioId, array $datos): CasUsuario
    {
        $usuario = $this->usuarios->find($usuarioId);
        if ($usuario === null) {
            throw new NotFoundHttpException('Usuario no encontrado.');
        }

        $casUsuario = $this->casUsuarios->buscarRelacion($cas, $usuario);
        if ($casUsuario === null) {
            throw new NotFoundHttpException('Ese usuario no está asociado a este CAS.');
        }

        if (isset($datos['documento'])) {
            $this->validarDocumentoDisponible($datos['documento'], $usuario);
            $usuario->setDocumento($datos['documento']);
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
        if (isset($datos['usuario'])) {
            $this->validarUsuarioDisponible($datos['usuario'], $usuario);
            $usuario->setUsuario($datos['usuario']);
        }
        if (isset($datos['email'])) {
            $this->validarEmailDisponible($datos['email'], $usuario);
            $usuario->setEmail($datos['email']);
        }
        if (array_key_exists('estado', $datos) && $datos['estado'] !== null) {
            // Estado de la relación cas.cas_usuario (este CAS con este
            // usuario), no de gbl.usuario — ver docblock del método.
            $casUsuario->setEstado((bool) $datos['estado']);
        }
        $this->actualizarPasswordSiViene($usuario, $datos);

        $usuario->tocarModificado();
        $this->em->flush();

        return $casUsuario;
    }

    /** @return CasUsuario[] */
    public function listarPorCas(Cas $cas): array
    {
        return $this->casUsuarios->listarPorCas($cas);
    }

    /** @return CasUsuario[] */
    public function listarTodos(): array
    {
        return $this->casUsuarios->listarTodos();
    }

    /**
     * CAS activos del usuario autenticado — para el selector de centro de
     * servicio de "Órdenes" (ver MisCasController).
     *
     * @return array<int, array{id: int, nombre: ?string, nit: ?string}>
     */
    public function misCas(Usuario $usuario): array
    {
        return $this->casUsuarios->misCasActivos($usuario->getId());
    }

    private function crearUsuario(string $documento, array $datos): Usuario
    {
        foreach (self::CAMPOS_REQUERIDOS_USUARIO_NUEVO as $campo) {
            if (trim((string) ($datos[$campo] ?? '')) === '') {
                throw new BadRequestHttpException(sprintf('El campo "%s" es requerido.', $campo));
            }
        }
        $this->passwordStrength->validar($datos['password']);
        $this->validarUsuarioDisponible($datos['usuario']);
        $this->validarEmailDisponible($datos['email']);

        $usuario = new Usuario();
        $usuario->setDocumento($documento);
        $usuario->setNombre($datos['nombre']);
        $usuario->setApellido($datos['apellido']);
        $usuario->setTelefono($datos['telefono'] ?? null);
        $usuario->setUsuario($datos['usuario']);
        $usuario->setEmail($datos['email']);
        $usuario->setEstado(true);
        $usuario->setPassword($this->passwordHasher->hashPassword($usuario, $datos['password']));

        $this->em->persist($usuario);

        return $usuario;
    }

    /** Punto 81: password vacío = no cambia; con valor = se valida y se re-hashea. */
    private function actualizarPasswordSiViene(Usuario $usuario, array $datos): void
    {
        $password = trim((string) ($datos['password'] ?? ''));
        if ($password === '') {
            return;
        }

        $this->passwordStrength->validar($password);
        $usuario->setPassword($this->passwordHasher->hashPassword($usuario, $password));
    }

    private function asegurarRoleCas(Usuario $usuario): void
    {
        $rolCas = $this->rolesPermiso->findOneByRole(self::ROLE_CAS);
        if ($rolCas === null) {
            throw new ConflictHttpException('El rol ROLE_CAS no existe en gbl.role_permiso.');
        }

        if ($this->usuarioRolesPermiso->buscarAsignacion($usuario, $rolCas) === null) {
            $this->em->persist(new UsuarioRolePermiso($usuario, $rolCas));
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

    private function validarEmailDisponible(string $email, ?Usuario $actual = null): void
    {
        $existente = $this->usuarios->findOneBy(['email' => $email]);
        if ($existente !== null && $existente->getId() !== $actual?->getId()) {
            throw new ConflictHttpException('Ese correo ya está en uso.');
        }
    }
}
