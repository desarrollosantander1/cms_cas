<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Agrega empleado_id a datacredito.reporte_factura (FK cruda → gbl.empleado,
 * mismo patrón que PeriodoCierre::$empleadoCierreId/Reclasificacion::$empleadoId
 * — ver ReporteFactura) — quién guardó/generó cada fila, no necesariamente
 * el mismo empleado que cerró el período (una fila puede haberse guardado a
 * mano antes del cierre por otro empleado).
 *
 * Backfill de filas existentes: se usa el empleado que cerró el período de
 * esa fila (mejor aproximación disponible sin historial más fino), con un
 * fallback al primer empleado del sistema por si el período todavía
 * estuviera abierto (no debería pasar hoy, pero la columna es NOT NULL).
 *
 * El diff automático también proponía dropear FKs de empleado_id/
 * empleado_cierre_id en periodo_cierre/plano_generado/reclasificacion y un
 * comentario de columna en cliente.cobro_juridico: drift preexistente y no
 * relacionado (mismo caso ya documentado en migraciones anteriores —
 * Doctrine no reconoce estas FK crudas como asociación propia), excluido a
 * propósito de esta migración.
 */
final class Version20260901145211 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega empleado_id a datacredito.reporte_factura (FK cruda a gbl.empleado)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE datacredito.reporte_factura ADD empleado_id INT DEFAULT NULL');
        $this->addSql(
            'UPDATE datacredito.reporte_factura rf
             SET empleado_id = COALESCE(
                 (SELECT p.empleado_cierre_id FROM datacredito.periodo_cierre p WHERE p.id = rf.periodo_cierre_id),
                 (SELECT MIN(id) FROM gbl.empleado)
             )'
        );
        $this->addSql('ALTER TABLE datacredito.reporte_factura ALTER COLUMN empleado_id SET NOT NULL');
        $this->addSql('ALTER TABLE datacredito.reporte_factura ADD CONSTRAINT fk_reporte_factura_empleado FOREIGN KEY (empleado_id) REFERENCES gbl.empleado (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('CREATE INDEX idx_reporte_factura_empleado ON datacredito.reporte_factura (empleado_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE datacredito.reporte_factura DROP CONSTRAINT fk_reporte_factura_empleado');
        $this->addSql('DROP INDEX datacredito.idx_reporte_factura_empleado');
        $this->addSql('ALTER TABLE datacredito.reporte_factura DROP empleado_id');
    }
}
