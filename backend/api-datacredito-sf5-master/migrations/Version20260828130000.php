<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Reestructuración usuario -> empleado (lado datacredito).
 *
 * Repunta las 3 FK crudas que apuntaban a gbl.usuario para que ahora
 * apunten a gbl.empleado, renombrando la columna a empleado(_cierre)_id:
 *   - periodo_cierre.usuario_cierre_id -> empleado_cierre_id  (ON DELETE SET NULL)
 *   - reclasificacion.usuario_id       -> empleado_id         (ON DELETE RESTRICT)
 *   - plano_generado.usuario_id        -> empleado_id         (ON DELETE RESTRICT)
 *
 * REQUIERE que la migración Version20260828120000 de API users sf4-1 ya
 * haya corrido: crea gbl.empleado y le hace backfill de un empleado por
 * cada usuario existente. El UPDATE de abajo mapea cada usuario_id viejo a
 * su empleado.id vía gbl.empleado.usuario_id.
 *
 * NO se tocan usuario_role_permiso, usuario_log ni usuario_password_reset
 * (siguen relacionadas contra gbl.usuario, a propósito).
 */
final class Version20260828130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'periodo_cierre / reclasificacion / plano_generado: FK usuario_id -> empleado_id (gbl.empleado)';
    }

    public function up(Schema $schema): void
    {
        // --- periodo_cierre.usuario_cierre_id -> empleado_cierre_id (nullable) ---
        $this->addSql('ALTER TABLE datacredito.periodo_cierre ADD empleado_cierre_id INT DEFAULT NULL');
        $this->addSql('UPDATE datacredito.periodo_cierre t SET empleado_cierre_id = e.id FROM gbl.empleado e WHERE e.usuario_id = t.usuario_cierre_id');
        $this->addSql('ALTER TABLE datacredito.periodo_cierre DROP CONSTRAINT fk_periodo_cierre_usuario_cierre');
        $this->addSql('ALTER TABLE datacredito.periodo_cierre DROP COLUMN usuario_cierre_id');
        $this->addSql('ALTER TABLE datacredito.periodo_cierre ADD CONSTRAINT fk_periodo_cierre_empleado_cierre FOREIGN KEY (empleado_cierre_id) REFERENCES gbl.empleado (id) ON DELETE SET NULL NOT DEFERRABLE');

        // --- reclasificacion.usuario_id -> empleado_id (NOT NULL) ---
        $this->addSql('ALTER TABLE datacredito.reclasificacion ADD empleado_id INT DEFAULT NULL');
        $this->addSql('UPDATE datacredito.reclasificacion t SET empleado_id = e.id FROM gbl.empleado e WHERE e.usuario_id = t.usuario_id');
        $this->addSql('ALTER TABLE datacredito.reclasificacion DROP CONSTRAINT fk_reclasificacion_usuario');
        $this->addSql('ALTER TABLE datacredito.reclasificacion DROP COLUMN usuario_id');
        $this->addSql('ALTER TABLE datacredito.reclasificacion ALTER empleado_id SET NOT NULL');
        $this->addSql('ALTER TABLE datacredito.reclasificacion ADD CONSTRAINT fk_reclasificacion_empleado FOREIGN KEY (empleado_id) REFERENCES gbl.empleado (id) ON DELETE RESTRICT NOT DEFERRABLE');

        // --- plano_generado.usuario_id -> empleado_id (NOT NULL) ---
        $this->addSql('ALTER TABLE datacredito.plano_generado ADD empleado_id INT DEFAULT NULL');
        $this->addSql('UPDATE datacredito.plano_generado t SET empleado_id = e.id FROM gbl.empleado e WHERE e.usuario_id = t.usuario_id');
        $this->addSql('ALTER TABLE datacredito.plano_generado DROP CONSTRAINT fk_plano_generado_usuario');
        $this->addSql('ALTER TABLE datacredito.plano_generado DROP COLUMN usuario_id');
        $this->addSql('ALTER TABLE datacredito.plano_generado ALTER empleado_id SET NOT NULL');
        $this->addSql('ALTER TABLE datacredito.plano_generado ADD CONSTRAINT fk_plano_generado_empleado FOREIGN KEY (empleado_id) REFERENCES gbl.empleado (id) ON DELETE RESTRICT NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // Reconstruye usuario_id/usuario_cierre_id desde empleado_id vía gbl.empleado.
        $this->addSql('ALTER TABLE datacredito.plano_generado ADD usuario_id INT DEFAULT NULL');
        $this->addSql('UPDATE datacredito.plano_generado t SET usuario_id = e.usuario_id FROM gbl.empleado e WHERE e.id = t.empleado_id');
        $this->addSql('ALTER TABLE datacredito.plano_generado DROP CONSTRAINT fk_plano_generado_empleado');
        $this->addSql('ALTER TABLE datacredito.plano_generado DROP COLUMN empleado_id');
        $this->addSql('ALTER TABLE datacredito.plano_generado ALTER usuario_id SET NOT NULL');
        $this->addSql('ALTER TABLE datacredito.plano_generado ADD CONSTRAINT fk_plano_generado_usuario FOREIGN KEY (usuario_id) REFERENCES gbl.usuario (id) ON DELETE RESTRICT NOT DEFERRABLE');

        $this->addSql('ALTER TABLE datacredito.reclasificacion ADD usuario_id INT DEFAULT NULL');
        $this->addSql('UPDATE datacredito.reclasificacion t SET usuario_id = e.usuario_id FROM gbl.empleado e WHERE e.id = t.empleado_id');
        $this->addSql('ALTER TABLE datacredito.reclasificacion DROP CONSTRAINT fk_reclasificacion_empleado');
        $this->addSql('ALTER TABLE datacredito.reclasificacion DROP COLUMN empleado_id');
        $this->addSql('ALTER TABLE datacredito.reclasificacion ALTER usuario_id SET NOT NULL');
        $this->addSql('ALTER TABLE datacredito.reclasificacion ADD CONSTRAINT fk_reclasificacion_usuario FOREIGN KEY (usuario_id) REFERENCES gbl.usuario (id) ON DELETE RESTRICT NOT DEFERRABLE');

        $this->addSql('ALTER TABLE datacredito.periodo_cierre ADD usuario_cierre_id INT DEFAULT NULL');
        $this->addSql('UPDATE datacredito.periodo_cierre t SET usuario_cierre_id = e.usuario_id FROM gbl.empleado e WHERE e.id = t.empleado_cierre_id');
        $this->addSql('ALTER TABLE datacredito.periodo_cierre DROP CONSTRAINT fk_periodo_cierre_empleado_cierre');
        $this->addSql('ALTER TABLE datacredito.periodo_cierre DROP COLUMN empleado_cierre_id');
        $this->addSql('ALTER TABLE datacredito.periodo_cierre ADD CONSTRAINT fk_periodo_cierre_usuario_cierre FOREIGN KEY (usuario_cierre_id) REFERENCES gbl.usuario (id) ON DELETE SET NULL NOT DEFERRABLE');
    }
}
