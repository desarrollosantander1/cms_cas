<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * gbl.empleado.netsuite_usuario_id -> netsuite_empleado_id (más preciso: es
 * el id del registro de EMPLEADO en NetSuite, no de un "usuario"). Columna
 * recién agregada en Version20260828120000, sin datos todavía.
 */
final class Version20260828140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'gbl.empleado: renombra netsuite_usuario_id -> netsuite_empleado_id (columna + índice único)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gbl.empleado RENAME COLUMN netsuite_usuario_id TO netsuite_empleado_id');
        $this->addSql('ALTER INDEX gbl.uq_empleado_netsuite_usuario_id RENAME TO uq_empleado_netsuite_empleado_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER INDEX gbl.uq_empleado_netsuite_empleado_id RENAME TO uq_empleado_netsuite_usuario_id');
        $this->addSql('ALTER TABLE gbl.empleado RENAME COLUMN netsuite_empleado_id TO netsuite_usuario_id');
    }
}
