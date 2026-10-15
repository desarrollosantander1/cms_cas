<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * gbl.menu.menu_grupo_id pasa a ON DELETE CASCADE, igual que ya lo son
 * menu_padre_id y menu_role_permiso.menu_id — así, eliminar un menu_grupo
 * elimina en cascada sus menús, submenús y relaciones de rol.
 */
final class Version20260821191322 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'menu.menu_grupo_id a ON DELETE CASCADE, para poder eliminar un menu_grupo completo';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gbl.menu DROP CONSTRAINT fk_fea390ccb0d27595');
        $this->addSql('ALTER TABLE gbl.menu ADD CONSTRAINT fk_fea390ccb0d27595 FOREIGN KEY (menu_grupo_id) REFERENCES gbl.menu_grupo (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gbl.menu DROP CONSTRAINT fk_fea390ccb0d27595');
        $this->addSql('ALTER TABLE gbl.menu ADD CONSTRAINT fk_fea390ccb0d27595 FOREIGN KEY (menu_grupo_id) REFERENCES gbl.menu_grupo (id) NOT DEFERRABLE');
    }
}
