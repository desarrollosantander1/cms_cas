<?php

namespace App\Command;

use App\Entity\Empleado;
use App\Entity\Menu;
use App\Entity\MenuGrupo;
use App\Entity\MenuRolePermiso;
use App\Entity\RolePermiso;
use App\Entity\UsuarioRolePermiso;
use App\Repository\MenuGrupoRepository;
use App\Repository\MenuRepository;
use App\Repository\RolePermisoRepository;
use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:seed-catalogo', description: 'Siembra la jerarquía de roles y el árbol de menú de ejemplo (idempotente)')]
class SeedCatalogoCommand extends Command
{
    public function __construct(
        private readonly RolePermisoRepository $roles,
        private readonly MenuGrupoRepository $menuGrupos,
        private readonly MenuRepository $menus,
        private readonly UsuarioRepository $usuarios,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // --- jerarquía de roles ---
        $admin = $this->rol('ROLE_ADMIN', null);
        $usuarioModulo = $this->rol('ROLE_USUARIO', $admin);
        $ver = $this->rol('ROLE_USUARIO_VER', $usuarioModulo);
        $crear = $this->rol('ROLE_USUARIO_CREAR', $usuarioModulo);
        $editar = $this->rol('ROLE_USUARIO_EDITAR', $usuarioModulo);
        $eliminar = $this->rol('ROLE_USUARIO_ELIMINAR', $usuarioModulo);
        $menuGestionar = $this->rol('ROLE_GESTIONAR_MENU', $admin);
        $this->rol('ROLE_ELIMINAR_GRUPO', $menuGestionar);
        $this->rol('ROLE_ELIMINAR_MENU', $menuGestionar);
        $this->rol('ROLE_GESTIONAR_ROLES', $admin);

        $this->em->flush();

        // --- menú ---
        $grupoAdmin = $this->menuGrupo('Administración', 'mdi-cog');
        $submenuUsuarios = $this->menu('Usuarios', $grupoAdmin, null, null);
        $mConsultar = $this->menu('Consultar', $grupoAdmin, $submenuUsuarios, '/administracion/usuarios');
        $mCrear = $this->menu('Crear', $grupoAdmin, $submenuUsuarios, '/administracion/usuarios');
        $mEditar = $this->menu('Editar', $grupoAdmin, $submenuUsuarios, '/administracion/usuarios');
        $mEliminar = $this->menu('Eliminar', $grupoAdmin, $submenuUsuarios, '/administracion/usuarios');
        $mGestionMenu = $this->menu('Gestión de menú', $grupoAdmin, null, '/administracion/menu');

        $this->em->flush();

        $this->menuRolePermiso($mConsultar, $ver);
        $this->menuRolePermiso($mCrear, $crear);
        $this->menuRolePermiso($mEditar, $editar);
        $this->menuRolePermiso($mEliminar, $eliminar);
        $this->menuRolePermiso($mGestionMenu, $menuGestionar);

        $this->em->flush();

        // --- asignación al usuario admin sembrado por API auth sf4-1 ---
        $adminUsuario = $this->usuarios->findOneByUsuario('admin');
        if ($adminUsuario !== null) {
            $yaAsignado = $this->em->getRepository(UsuarioRolePermiso::class)->findOneBy(['usuario' => $adminUsuario, 'rolePermiso' => $admin]);
            if ($yaAsignado === null) {
                $this->em->persist(new UsuarioRolePermiso($adminUsuario, $admin));
                $this->em->flush();
            }

            // El usuario "admin" lo crea API auth sf4-1 (no pasa por
            // UsuarioService::crear), así que su empleado 1:1 se siembra aquí.
            $tieneEmpleado = $this->em->getRepository(Empleado::class)->findOneBy(['usuario' => $adminUsuario]);
            if ($tieneEmpleado === null) {
                $this->em->persist(new Empleado($adminUsuario));
                $this->em->flush();
            }
            $io->success('Roles y menú sembrados. ROLE_ADMIN asignado al usuario "admin" (hereda todo lo que cuelga debajo).');
        } else {
            $io->warning('Roles y menú sembrados, pero no existe el usuario "admin" todavía (correr app:seed-usuario en API auth sf4-1 primero).');
        }

        $io->note('ROLE_USUARIO_VER queda sin usuarios asignados directamente — asígnalo a un usuario de prueba para ver la poda del menú.');

        return Command::SUCCESS;
    }

    private function rol(string $role, ?RolePermiso $padre): RolePermiso
    {
        $existente = $this->roles->findOneByRole($role);
        if ($existente !== null) {
            return $existente;
        }

        $rp = new RolePermiso();
        $rp->setRole($role);
        $rp->setRolePadre($padre);
        $this->em->persist($rp);

        return $rp;
    }

    private function menuGrupo(string $grupo, string $icono): MenuGrupo
    {
        $existente = $this->menuGrupos->findOneByGrupo($grupo);
        if ($existente !== null) {
            return $existente;
        }

        $mg = new MenuGrupo();
        $mg->setGrupo($grupo);
        $mg->setIcono($icono);
        $this->em->persist($mg);
        $this->em->flush();

        return $mg;
    }

    private function menu(string $etiqueta, MenuGrupo $grupo, ?Menu $padre, ?string $ruta): Menu
    {
        $existente = $this->menus->findOneByMenu($etiqueta);
        if ($existente !== null) {
            return $existente;
        }

        $m = new Menu();
        $m->setMenu($etiqueta);
        $m->setMenuGrupo($grupo);
        $m->setMenuPadre($padre);
        $m->setRuta($ruta);
        $this->em->persist($m);

        return $m;
    }

    private function menuRolePermiso(Menu $menu, RolePermiso $rolePermiso): void
    {
        $existente = $this->em->getRepository(MenuRolePermiso::class)->findOneBy(['menu' => $menu, 'rolePermiso' => $rolePermiso]);
        if ($existente === null) {
            $this->em->persist(new MenuRolePermiso($menu, $rolePermiso));
        }
    }
}
