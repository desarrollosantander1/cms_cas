<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Dos ajustes sobre las tablas nuevas de Version20260929150000:
 *
 *  - Renombra gbl.calidad_producto_tipo a gbl.producto_calidad_tipo, para
 *    alinearla con el resto de la familia `producto_*` (producto_adjunto,
 *    producto_repuesto, producto_calidad, producto_material). La FK de
 *    gbl.producto_calidad.calidad_tipo_id sigue apuntando a la misma tabla
 *    sin cambios (Postgres la resuelve por OID, no por nombre); se renombra
 *    también la PK para mantener la convención `pk_<tabla>`.
 *  - Agrega a gbl.producto_adjunto las columnas `ruta`, `nombre` (NOT NULL)
 *    y `extension`, que faltaban para poder guardar la ruta/nombre real del
 *    archivo adjunto (hasta ahora solo tenía metadata de tipo/estado).
 *    `nombre` se agrega con DEFAULT '' y se quita enseguida — igual truco
 *    que Version20260928120000 — para poder volverla NOT NULL sin romper
 *    filas existentes (la tabla está vacía, pero se sigue la convención).
 */
final class Version20260929160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renombra gbl.calidad_producto_tipo a producto_calidad_tipo y agrega ruta/nombre/extension a gbl.producto_adjunto';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gbl.calidad_producto_tipo RENAME TO producto_calidad_tipo');
        $this->addSql('ALTER TABLE gbl.producto_calidad_tipo RENAME CONSTRAINT pk_calidad_producto_tipo TO pk_producto_calidad_tipo');

        $this->addSql('ALTER TABLE gbl.producto_adjunto ADD COLUMN ruta varchar(510)');
        $this->addSql("ALTER TABLE gbl.producto_adjunto ADD COLUMN nombre varchar(510) NOT NULL DEFAULT ''");
        $this->addSql('ALTER TABLE gbl.producto_adjunto ALTER COLUMN nombre DROP DEFAULT');
        $this->addSql('ALTER TABLE gbl.producto_adjunto ADD COLUMN extension varchar(255)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gbl.producto_adjunto DROP COLUMN extension');
        $this->addSql('ALTER TABLE gbl.producto_adjunto DROP COLUMN nombre');
        $this->addSql('ALTER TABLE gbl.producto_adjunto DROP COLUMN ruta');

        $this->addSql('ALTER TABLE gbl.producto_calidad_tipo RENAME CONSTRAINT pk_producto_calidad_tipo TO pk_calidad_producto_tipo');
        $this->addSql('ALTER TABLE gbl.producto_calidad_tipo RENAME TO calidad_producto_tipo');
    }
}
