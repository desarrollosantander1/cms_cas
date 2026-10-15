<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajusta fk_orden_adjunto_adjunto_tipo_id para que quede ON DELETE SET NULL,
 * igual que declara OrdenAdjunto::$adjuntoTipo (JoinColumn nullable +
 * onDelete: 'SET NULL'). Postgres no permite ALTER de la cláusula ON DELETE
 * de una FK existente, así que se recrea (drop + add).
 */
final class Version20260924205036 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'fk_orden_adjunto_adjunto_tipo_id queda ON DELETE SET NULL';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cas.orden_adjunto DROP CONSTRAINT fk_orden_adjunto_adjunto_tipo_id');
        $this->addSql('ALTER TABLE cas.orden_adjunto ADD CONSTRAINT fk_orden_adjunto_adjunto_tipo_id FOREIGN KEY (adjunto_tipo_id) REFERENCES gbl.adjunto_tipo(id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cas.orden_adjunto DROP CONSTRAINT fk_orden_adjunto_adjunto_tipo_id');
        $this->addSql('ALTER TABLE cas.orden_adjunto ADD CONSTRAINT fk_orden_adjunto_adjunto_tipo_id FOREIGN KEY (adjunto_tipo_id) REFERENCES gbl.adjunto_tipo(id)');
    }
}
