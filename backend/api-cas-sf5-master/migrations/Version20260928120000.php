<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Agrega gbl.unidad_medida.codigo (varchar(255) NOT NULL) — abreviatura corta
 * de la unidad (ej. "UN", "M", "KG"), que hasta ahora solo tenía `descripcion`.
 *
 * Escrita a mano con SQL crudo, como el resto de los cambios sobre `gbl.*` de
 * este servicio: `dbal.schema_filter` solo admite `cas.*`, así que
 * `migrations:diff` nunca propone estas sentencias (ver doctrine.yaml).
 *
 * La columna se agrega con `DEFAULT ''` y el default se quita enseguida: es la
 * única forma de volverla NOT NULL sin romper las filas que ya existan en el
 * catálogo, y al quitar el default los INSERT futuros quedan obligados a dar
 * un valor (que es lo que declara UnidadMedida::__construct()).
 */
final class Version20260928120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega la columna codigo (varchar(255) NOT NULL) a gbl.unidad_medida';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE gbl.unidad_medida ADD COLUMN codigo varchar(255) NOT NULL DEFAULT ''");
        $this->addSql('ALTER TABLE gbl.unidad_medida ALTER COLUMN codigo DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gbl.unidad_medida DROP COLUMN codigo');
    }
}
