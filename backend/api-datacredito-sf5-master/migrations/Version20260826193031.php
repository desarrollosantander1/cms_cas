<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Alinea el nombre del índice de la asociación mapeada
 * PlanoGenerado->PeriodoCierre con la convención auto-generada de Doctrine
 * — mismo criterio que Version20260826155235/Version20260826171608.
 *
 * Generada vía migrations:diff y limpiada a mano: se quitaron los DROP
 * CONSTRAINT/DROP INDEX fantasma de fk_periodo_cierre_usuario_cierre,
 * fk_plano_generado_usuario (ahora 3 de 3 tablas con FK cruda a
 * gbl.usuario exhiben este gotcha) y fk_reclasificacion_usuario — columnas
 * FK crudas sin asociación Doctrine mapeada, migrations:diff las propone
 * eliminar en cada corrida; no son un cambio real, no se aplican. También
 * se quitó el CREATE SCHEMA public espurio de down().
 */
final class Version20260826193031 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Alinea nombre de índice de PlanoGenerado->PeriodoCierre con la convención de Doctrine';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER INDEX datacredito.idx_plano_generado_periodo_cierre_id RENAME TO IDX_AF205E8DC832F2E9');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER INDEX datacredito.idx_af205e8dc832f2e9 RENAME TO idx_plano_generado_periodo_cierre_id');
    }
}
