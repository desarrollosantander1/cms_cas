<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Agrega cas.orden.diagnostico y cas.orden.reporte_final: a diferencia del resto de columnas de
 * esta tabla, no se piden en el formulario de creación de la orden — se habilitan después, en un
 * paso posterior del flujo (ej. cuando el técnico atiende la orden), por eso quedan nullable sin
 * default.
 */
final class Version20261012192602 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega cas.orden.diagnostico y cas.orden.reporte_final';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cas.orden ADD COLUMN diagnostico varchar(255)');
        $this->addSql("COMMENT ON COLUMN cas.orden.diagnostico IS 'Diagnóstico técnico de la orden; se habilita después de creada la orden, no se solicita en el formulario de creación'");

        $this->addSql('ALTER TABLE cas.orden ADD COLUMN reporte_final varchar(255)');
        $this->addSql("COMMENT ON COLUMN cas.orden.reporte_final IS 'Reporte final de la orden; se habilita después de creada la orden, no se solicita en el formulario de creación'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cas.orden DROP COLUMN reporte_final');
        $this->addSql('ALTER TABLE cas.orden DROP COLUMN diagnostico');
    }
}
