<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Agrega "orden" a menu_grupo y menu para permitir personalizar el orden de
 * despliegue (entre grupos, y entre menús hermanos de un mismo grupo o padre).
 */
final class Version20260821142426 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega la columna orden a gbl.menu_grupo y gbl.menu';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gbl.menu_grupo ADD orden INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE gbl.menu ADD orden INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gbl.menu_grupo DROP orden');
        $this->addSql('ALTER TABLE gbl.menu DROP orden');
    }
}
