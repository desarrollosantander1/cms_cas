<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Agrega gbl.adjunto_tipo.directorio y elimina cas.orden_adjunto.ruta: la
 * ruta de cada adjunto deja de guardarse por archivo (ya no hace falta saber
 * en qué subcarpeta cayó cada uno) y pasa a armarse en tiempo de consulta a
 * partir de columnas que ya existen — adjunto_tipo.base_url/path_adjunto +
 * este `directorio` (el nombre fijo de subcarpeta del tipo, ej.
 * "orden-adjuntos") + el id del cas/orden + el id/extension del propio
 * adjunto. Ver ArchivoAdjuntoUploader y OrdenService.
 *
 * `directorio` nullable y sin dato semilla aquí: igual que `base_url` y
 * `path_adjunto` (Version20260924203625), el valor por fila de
 * gbl.adjunto_tipo se configura a mano, no por migración.
 *
 * `down()` reagrega `ruta` nullable (no NOT NULL como el original): los
 * valores de ruta que tuviera cada adjunto no se pueden recuperar al hacer
 * rollback, así que no se puede exigir la columna sin default.
 */
final class Version20261008202104 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega gbl.adjunto_tipo.directorio y elimina cas.orden_adjunto.ruta';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gbl.adjunto_tipo ADD COLUMN directorio varchar(255)');
        $this->addSql('ALTER TABLE cas.orden_adjunto DROP COLUMN ruta');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cas.orden_adjunto ADD COLUMN ruta varchar(510)');
        $this->addSql('ALTER TABLE gbl.adjunto_tipo DROP COLUMN directorio');
    }
}
