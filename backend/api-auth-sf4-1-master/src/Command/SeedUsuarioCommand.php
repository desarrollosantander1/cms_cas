<?php

namespace App\Command;

use App\Entity\Usuario;
use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:seed-usuario', description: 'Crea un usuario de prueba para probar el login (idempotente)')]
class SeedUsuarioCommand extends Command
{
    public function __construct(
        private readonly UsuarioRepository $usuarios,
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $existing = $this->usuarios->findOneByUsuario('admin');
        if ($existing !== null) {
            $io->info('El usuario "admin" ya existe (id ' . $existing->getId() . ') — no se crea de nuevo.');

            return Command::SUCCESS;
        }

        $usuario = new Usuario();
        $usuario->setDocumento('0000000000');
        $usuario->setNombre('Administrador');
        $usuario->setApellido('HEOS');
        $usuario->setUsuario('admin');
        $usuario->setEmail('admin@heos.local');
        $usuario->setEstado(true);
        $usuario->setPoliticaDatos(true);
        $usuario->setPassword($this->passwordHasher->hashPassword($usuario, 'Admin_2026!'));

        $this->em->persist($usuario);
        $this->em->flush();

        $io->success(sprintf('Usuario de prueba creado — usuario: admin / password: Admin_2026! (id %d)', $usuario->getId()));

        return Command::SUCCESS;
    }
}
