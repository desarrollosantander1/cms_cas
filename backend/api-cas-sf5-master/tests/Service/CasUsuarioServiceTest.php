<?php

namespace App\Tests\Service;

use App\Entity\Cas;
use App\Entity\CasUsuario;
use App\Entity\Gbl\RolePermiso;
use App\Entity\Gbl\Usuario;
use App\Entity\Gbl\UsuarioRolePermiso;
use App\Repository\CasUsuarioRepository;
use App\Repository\Gbl\RolePermisoRepository;
use App\Repository\Gbl\UsuarioRepository;
use App\Repository\Gbl\UsuarioRolePermisoRepository;
use App\Service\CasUsuarioService;
use App\Service\PasswordStrengthValidator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Cubre la regla de negocio más sensible del módulo CAS (puntos 79-90 del
 * encargo original): no duplicar gbl.usuario, no duplicar la relación
 * cas_usuario, y garantizar ROLE_CAS sin duplicarlo tampoco.
 */
class CasUsuarioServiceTest extends TestCase
{
    private UsuarioRepository $usuarios;
    private CasUsuarioRepository $casUsuarios;
    private RolePermisoRepository $rolesPermiso;
    private UsuarioRolePermisoRepository $usuarioRolesPermiso;
    private UserPasswordHasherInterface $passwordHasher;
    private EntityManagerInterface $em;
    private CasUsuarioService $service;

    protected function setUp(): void
    {
        $this->usuarios = $this->createMock(UsuarioRepository::class);
        $this->casUsuarios = $this->createMock(CasUsuarioRepository::class);
        $this->rolesPermiso = $this->createMock(RolePermisoRepository::class);
        $this->usuarioRolesPermiso = $this->createMock(UsuarioRolePermisoRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->service = new CasUsuarioService(
            $this->usuarios,
            $this->casUsuarios,
            $this->rolesPermiso,
            $this->usuarioRolesPermiso,
            $this->passwordHasher,
            new PasswordStrengthValidator(),
            $this->em,
        );
    }

    public function testAsociarCreaUnUsuarioNuevoCuandoNoExistePorDocumento(): void
    {
        $cas = new Cas();
        $rolCas = new RolePermiso();

        $this->usuarios->method('findOneByDocumento')->with('555000111')->willReturn(null);
        $this->usuarios->method('findOneByUsuario')->willReturn(null);
        $this->passwordHasher->method('hashPassword')->willReturn('hash-simulado');
        $this->rolesPermiso->method('findOneByRole')->with('ROLE_CAS')->willReturn($rolCas);
        $this->usuarioRolesPermiso->method('buscarAsignacion')->willReturn(null);
        $this->casUsuarios->method('existe')->willReturn(false);

        $this->em->expects($this->exactly(3))->method('persist')->with($this->logicalOr(
            $this->isInstanceOf(Usuario::class),
            $this->isInstanceOf(UsuarioRolePermiso::class),
            $this->isInstanceOf(CasUsuario::class),
        ));
        $this->em->expects($this->once())->method('flush');

        $casUsuario = $this->service->asociar($cas, [
            'documento' => '555000111',
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'usuario' => 'jperezcas',
            'email' => 'jperez@example.com',
            'password' => 'Abcd1234!',
        ]);

        self::assertSame('555000111', $casUsuario->getUsuario()->getDocumento());
        self::assertSame($cas, $casUsuario->getCas());
    }

    public function testAsociarNoCreaOtroUsuarioSiYaExistePorDocumento(): void
    {
        $cas = new Cas();
        $usuarioExistente = new Usuario();
        $usuarioExistente->setDocumento('555000111');

        $this->usuarios->method('findOneByDocumento')->with('555000111')->willReturn($usuarioExistente);
        $this->rolesPermiso->method('findOneByRole')->willReturn(new RolePermiso());
        $this->usuarioRolesPermiso->method('buscarAsignacion')->willReturn(new UsuarioRolePermiso($usuarioExistente, new RolePermiso()));
        $this->casUsuarios->method('existe')->willReturn(false);

        // Solo debe persistir la nueva relación CasUsuario — nunca un Usuario nuevo.
        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(CasUsuario::class));
        $this->em->expects($this->once())->method('flush');

        $casUsuario = $this->service->asociar($cas, ['documento' => '555000111']);

        self::assertSame($usuarioExistente, $casUsuario->getUsuario());
    }

    public function testAsociarRechazaUnUsuarioYaAsociadoAlMismoCas(): void
    {
        $cas = new Cas();
        $usuarioExistente = new Usuario();

        $this->usuarios->method('findOneByDocumento')->willReturn($usuarioExistente);
        $this->casUsuarios->method('existe')->willReturn(true);

        $this->expectException(ConflictHttpException::class);
        $this->service->asociar($cas, ['documento' => '555000111']);
    }

    public function testAsociarExigeCamposRequeridosParaUnUsuarioNuevo(): void
    {
        $cas = new Cas();
        $this->usuarios->method('findOneByDocumento')->willReturn(null);

        $this->expectException(BadRequestHttpException::class);
        $this->service->asociar($cas, ['documento' => '555000111']);
    }

    public function testAsociarNoDuplicaRoleCasSiElUsuarioYaLoTiene(): void
    {
        $cas = new Cas();
        $usuarioExistente = new Usuario();
        $asignacionExistente = new UsuarioRolePermiso($usuarioExistente, new RolePermiso());

        $this->usuarios->method('findOneByDocumento')->willReturn($usuarioExistente);
        $this->rolesPermiso->method('findOneByRole')->willReturn(new RolePermiso());
        $this->usuarioRolesPermiso->method('buscarAsignacion')->willReturn($asignacionExistente);
        $this->casUsuarios->method('existe')->willReturn(false);

        // Solo la relación CasUsuario — nunca un UsuarioRolePermiso nuevo.
        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(CasUsuario::class));

        $this->service->asociar($cas, ['documento' => '555000111']);
    }
}
