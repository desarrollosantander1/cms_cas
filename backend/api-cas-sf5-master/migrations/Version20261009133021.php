<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Vuelve a agregar cas.orden_adjunto.ruta, eliminada en Version20261008202104.
 * A diferencia de aquel intento (donde `ruta` guardaba una ruta/subcarpeta
 * completa), ahora separa dos cosas que antes se mezclaban en `nombre`:
 * - `nombre` sigue siendo el nombre original tal como se subió (para
 *   mostrárselo al usuario).
 * - `ruta` es el nombre real con el que el archivo quedó en disco — igual a
 *   `nombre` salvo que haya chocado con otro adjunto de la misma orden con
 *   el mismo nombre original, caso en que ArchivoAdjuntoUploader lo
 *   desambigua (ej. "foto (1).jpg") — ver OrdenController::urlAdjunto(),
 *   que ahora arma la URL de descarga con `ruta`, no con `nombre`.
 *
 * Se agrega NOT NULL (como el `ruta` original) en dos pasos porque la tabla
 * ya tiene filas de prueba: se backfillea con el valor de `nombre` (hoy
 * coincide siempre, ya que todavía no hay colisiones reales) antes de
 * exigir la columna.
 */
final class Version20261009133021 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Vuelve a agregar cas.orden_adjunto.ruta (nombre real en disco, separado de `nombre`)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cas.orden_adjunto ADD COLUMN ruta varchar(510)');
        $this->addSql('UPDATE cas.orden_adjunto SET ruta = nombre WHERE ruta IS NULL');
        $this->addSql('ALTER TABLE cas.orden_adjunto ALTER COLUMN ruta SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cas.orden_adjunto DROP COLUMN ruta');
    }
}
