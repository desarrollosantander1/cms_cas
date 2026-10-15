<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260819212247 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renombra gbl.password_reset_token a gbl.usuario_password_reset';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gbl.password_reset_token RENAME TO usuario_password_reset');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gbl.usuario_password_reset RENAME TO password_reset_token');
    }
}
