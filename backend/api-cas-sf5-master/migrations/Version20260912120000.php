<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Crea el esquema propio del microservicio CAS. Se hace en su propia
 * migración (en vez de junto con la primera tabla real) para que la creación
 * del esquema quede fácil de identificar en el historial, y porque el
 * bookkeeping de migraciones vive en gbl.doctrine_migration_versions
 * (compartido con auth/users/datacredito) — este esquema todavía no existe
 * cuando esa tabla se crea, así que no puede asumirse presente de antemano.
 */
final class Version20260912120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea el esquema cas';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS cas');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SCHEMA IF EXISTS cas CASCADE');
    }
}
