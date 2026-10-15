<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Agrega la FK cas.orden_detalle.producto_id -> gbl.producto(id), que
 * Version20260912120100 nunca creó (a diferencia de cas.orden.producto_id,
 * que sí la tiene) — un vacío del script original, no una decisión
 * deliberada. Se corrige ahora porque `OrdenDetalle::$producto` pasa de
 * columna simple a relación `#[ORM\ManyToOne]` (ver ese docblock), y
 * `doctrine:schema:validate` la exige para quedar en sincronía.
 *
 * Sin ON DELETE explícito (NO ACTION), igual que fk_orden_producto_id.
 */
final class Version20260929130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega la FK faltante cas.orden_detalle.producto_id -> gbl.producto(id)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cas.orden_detalle ADD CONSTRAINT fk_orden_detalle_producto_id FOREIGN KEY (producto_id) REFERENCES gbl.producto(id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cas.orden_detalle DROP CONSTRAINT fk_orden_detalle_producto_id');
    }
}
