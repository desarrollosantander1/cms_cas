<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Agrega a cas.cas la configuración de adjuntos de órdenes que hoy vive
 * quemada en código (ArchivoAdjuntoUploader::MIME_PERMITIDOS/
 * TAMANO_MAXIMO_BYTES): cada CAS puede tener su propia lista de tipos de
 * archivo permitidos y sus propios topes de tamaño, en vez de un valor
 * único global para todos.
 *
 * `maximo_mb_orden` es nuevo (no reemplaza ninguna constante existente):
 * antes solo se validaba el tamaño de CADA archivo por separado, nunca la
 * suma de los adjuntos de una misma orden — ver OrdenService.
 *
 * Los tres quedan NOT NULL con DEFAULT (no hace falta el patrón de
 * backfill en dos pasos de Version20261009133021): Postgres aplica el
 * default a las filas existentes en el mismo ALTER TABLE.
 */
final class Version20261009203340 extends AbstractMigration
{
    private const MIME_PERMITIDOS_DEFECTO = 'application/pdf, image/jpeg, image/png, image/webp, image/gif, image/heic, image/heif, video/mp4, video/quicktime, video/webm, video/x-msvideo, video/3gpp';

    public function getDescription(): string
    {
        return 'Agrega cas.cas.mime_permitidos/maximo_mb_archivo/maximo_mb_orden (config de adjuntos por CAS)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(sprintf(
            "ALTER TABLE cas.cas ADD COLUMN mime_permitidos varchar(255) NOT NULL DEFAULT '%s'",
            self::MIME_PERMITIDOS_DEFECTO,
        ));
        $this->addSql("COMMENT ON COLUMN cas.cas.mime_permitidos IS 'Para saber que tipos de archivo se pueden cargar por centro de servicio para las ordenes de reparación'");

        $this->addSql('ALTER TABLE cas.cas ADD COLUMN maximo_mb_archivo numeric(12,2) NOT NULL DEFAULT 50');
        $this->addSql("COMMENT ON COLUMN cas.cas.maximo_mb_archivo IS 'Para configurar el tamaño máximo en MB permitido por archivo adjunto de una orden de reparación'");

        $this->addSql('ALTER TABLE cas.cas ADD COLUMN maximo_mb_orden numeric(12,2) NOT NULL DEFAULT 150');
        $this->addSql("COMMENT ON COLUMN cas.cas.maximo_mb_orden IS 'Para configurar el tamaño máximo en MB permitido por orden de reparación (suma de todos sus archivos adjuntos)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cas.cas DROP COLUMN maximo_mb_orden');
        $this->addSql('ALTER TABLE cas.cas DROP COLUMN maximo_mb_archivo');
        $this->addSql('ALTER TABLE cas.cas DROP COLUMN mime_permitidos');
    }
}
