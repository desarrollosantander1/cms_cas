<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Prepara la base para la creación de órdenes de servicio (ver
 * Entity/OrdenService.php):
 *
 *  - Siembra gbl.producto_calidad_tipo con los 4 valores que el propio
 *    comentario de la tabla ya documentaba (Version20260929150000) —
 *    "GARANTIA" es el que usa ProductoGarantiaService para encontrar la fila
 *    de gbl.producto_calidad que aplica a un producto.
 *  - Siembra cas.orden_estado con un único estado "PENDIENTE": es el que se
 *    asigna a toda orden nueva. El resto del catálogo de estados (flujo de
 *    transición completo) queda fuera de este alcance.
 *  - Agrega UNIQUE(cas_id, numero) en cas.orden: respaldo de integridad para
 *    la numeración secuencial por CAS (ver OrdenRepository::siguienteNumero()).
 *  - Corrige cas.cas_cliente: la UNIQUE original era solo sobre
 *    numero_identidad (global), pero la regla de negocio de creación de
 *    orden busca "si el cliente ya existe PARA ESE CAS" (ver
 *    CasClienteService) — una misma cédula debe poder repetirse en distintos
 *    CAS (la misma persona compra en más de un centro de servicio), solo no
 *    duplicarse dentro del mismo CAS. Se reemplaza por UNIQUE(cas_id,
 *    numero_identidad). No hay filas existentes que puedan chocar (la tabla
 *    está vacía en esta fase).
 */
final class Version20260929170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Siembra producto_calidad_tipo/orden_estado y agrega UNIQUE(cas_id, numero) en cas.orden';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO gbl.producto_calidad_tipo (descripcion) VALUES ('GARANTIA'), ('VOLTAJE'), ('FRECUENCIA'), ('POTENCIA')");
        $this->addSql("INSERT INTO cas.orden_estado (descripcion) VALUES ('PENDIENTE')");
        $this->addSql('ALTER TABLE cas.orden ADD CONSTRAINT uq_orden_cas_numero UNIQUE (cas_id, numero)');

        $this->addSql('ALTER TABLE cas.cas_cliente DROP CONSTRAINT uq_cas_cliente_numero_identidad');
        $this->addSql('ALTER TABLE cas.cas_cliente ADD CONSTRAINT uq_cas_cliente_cas_numero_identidad UNIQUE (cas_id, numero_identidad)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cas.cas_cliente DROP CONSTRAINT uq_cas_cliente_cas_numero_identidad');
        $this->addSql('ALTER TABLE cas.cas_cliente ADD CONSTRAINT uq_cas_cliente_numero_identidad UNIQUE (numero_identidad)');

        $this->addSql('ALTER TABLE cas.orden DROP CONSTRAINT uq_orden_cas_numero');
        $this->addSql("DELETE FROM cas.orden_estado WHERE descripcion = 'PENDIENTE'");
        $this->addSql("DELETE FROM gbl.producto_calidad_tipo WHERE descripcion IN ('GARANTIA', 'VOLTAJE', 'FRECUENCIA', 'POTENCIA')");
    }
}
