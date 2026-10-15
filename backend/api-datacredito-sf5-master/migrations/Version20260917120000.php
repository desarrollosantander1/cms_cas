<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Siembra en datacredito.configuracion el resto del layout físico del plano
 * TXT de DataCrédito (anchos de campo, literales fijos de cabecera/cola y
 * fillers) que hasta ahora vivían como `private const` en GeneradorTxtService
 * — ver LayoutPlanoTxt / ConfiguracionService::layoutPlanoTxt().
 *
 * ADVERTENCIA: a diferencia de los 14 valores institucionales/de negocio
 * sembrados en Version20260916120000, estos 33 valores son el contrato
 * físico del archivo de 800 caracteres que exige DataCrédito — no son un
 * parámetro de negocio ajustable a discreción. Un ancho mal editado no
 * rompe silenciosamente: GeneradorTxtService::exigirAncho() revienta con
 * una excepción explícita en cuanto la suma de campos de una línea no da
 * exactamente 800, pero solo al generar el reporte, no al guardar el valor.
 * Cualquier edición debe validarse regenerando un plano contra un período
 * real y comparando byte a byte contra la especificación de DataCrédito.
 */
final class Version20260917120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Siembra en datacredito.configuracion el layout físico completo del plano TXT (anchos/literales/fillers)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            INSERT INTO datacredito.configuracion (codigo, valor, descripcion) VALUES
              ('REPORTE_ANCHO_LINEA', '800', 'Ancho total (caracteres) de cada línea del plano: cabecera, cuerpo y cola.'),
              ('REPORTE_CAB_REGISTRO_INICIAL', 'HHHHHHHHHHHHHHHHHH', 'Literal fijo (ancho 18) que abre la línea de cabecera del plano.'),
              ('REPORTE_ANCHO_FECHA', '8', 'Ancho de todo campo de fecha YYYYMMDD (cabecera, cuerpo y cola).'),
              ('REPORTE_CAB_AMPLIACION_MILENIO', 'M', 'Literal fijo (ancho 1) de la cabecera: indica año en 4 dígitos.'),
              ('REPORTE_CAB_INDICADOR_VALORES_MILES', ' ', 'Literal fijo (ancho 1) de la cabecera: ''M'' = valores en miles; no aplica en este reporte.'),
              ('REPORTE_CAB_TIPO_ENTREGA', 'T', 'Literal fijo (ancho 1) de la cabecera: tipo de entrega (T = actualización mes).'),
              ('REPORTE_CAB_FECHA_INICIO_REPORTE', '00000000', 'Literal fijo (ancho 8) de la cabecera: fecha de inicio de reporte, no aplica en este formato.'),
              ('REPORTE_CAB_FECHA_FIN_REPORTE', '00000000', 'Literal fijo (ancho 8) de la cabecera: fecha de fin de reporte, no aplica en este formato.'),
              ('REPORTE_CAB_INDICADOR_PARTIR', 'N', 'Literal fijo (ancho 1) de la cabecera: indicador de más de un suscriptor por obligación.'),
              ('REPORTE_CAB_ANCHO_FILLER', '746', 'Ancho del filler de relleno de la cabecera. Debe ser 800 menos la suma de todos los demás campos de la cabecera (hoy 54).'),
              ('REPORTE_ANCHO_TIPO_IDENTIFICACION', '1', 'Ancho del campo TIPO_IDENTIFICACION del cuerpo.'),
              ('REPORTE_ANCHO_NUMERO_IDENTIFICACION', '11', 'Ancho del campo NUMERO_IDENTIFICACION del cuerpo.'),
              ('REPORTE_ANCHO_NUMERO_CUENTA_OBLIGACION', '18', 'Ancho del campo NUMERO_CUENTA_OBLIGACION del cuerpo.'),
              ('REPORTE_ANCHO_NOMBRE_COMPLETO', '45', 'Ancho del campo NOMBRE_COMPLETO del cuerpo.'),
              ('REPORTE_LARGO_MAXIMO_NOMBRE_COMPLETO', '44', 'Largo máximo al que se trunca el nombre antes del relleno a REPORTE_ANCHO_NOMBRE_COMPLETO (debe ser exactamente 1 menos que ese ancho).'),
              ('REPORTE_ANCHO_RESPONSABLE_CALIDAD_DEUDOR', '2', 'Ancho de RESPONSABLE_CALIDAD_DEUDOR y ESTADO_CUENTA del cuerpo.'),
              ('REPORTE_ANCHO_NOMBRE_MARCA_PRIVACIDAD', '30', 'Ancho de NOMBRE_MARCA_PRIVACIDAD y OFICINA_RADICACION del cuerpo.'),
              ('REPORTE_ANCHO_CALIFICACION', '2', 'Ancho del campo CALIFICACION del cuerpo.'),
              ('REPORTE_ANCHO_PROBABILIDAD_INCUMPLIMIENTO', '3', 'Ancho del campo PROBABILIDAD_INCUMPLIMIENTO del cuerpo.'),
              ('REPORTE_ANCHO_EDAD_INCUMPLIMIENTO', '3', 'Ancho del campo EDAD_INCUMPLIMIENTO del cuerpo.'),
              ('REPORTE_ANCHO_VALOR_MONETARIO', '11', 'Ancho de cada campo monetario del cuerpo (cupo/saldo/valor_cuota/valor_saldo_mora).'),
              ('REPORTE_ANCHO_CUOTAS', '3', 'Ancho de cada campo de cuotas del cuerpo (total/canceladas/mora).'),
              ('REPORTE_ANCHO_CIUDAD', '20', 'Ancho de cada campo de ciudad/departamento del cuerpo (residencia/laboral/correspondencia/radicación).'),
              ('REPORTE_ANCHO_CODIGO_DANE', '8', 'Ancho de cada campo de código DANE del cuerpo.'),
              ('REPORTE_ANCHO_DIRECCION', '60', 'Ancho de cada campo de dirección del cuerpo.'),
              ('REPORTE_ANCHO_TELEFONO', '12', 'Ancho de cada campo de teléfono/celular del cuerpo.'),
              ('REPORTE_ANCHO_CORREO', '60', 'Ancho del campo CORREO_ELECTRONICO del cuerpo.'),
              ('REPORTE_ANCHO_SUSCRIPTOR_DESTINO', '6', 'Ancho nominal del campo SUSCRIPTOR_DESTINO del cuerpo (hoy no se aplica como padding, ver docblock de GeneradorTxtService).'),
              ('REPORTE_ANCHO_NUMERO_TARJETA', '18', 'Ancho del campo NUMERO_TARJETA del cuerpo.'),
              ('REPORTE_COLA_IDENTIFICADOR', 'ZZZZZZZZZZZZZZZZZZ', 'Literal fijo (ancho 18) que abre la línea de cola del plano.'),
              ('REPORTE_ANCHO_NUMERO_REGISTROS', '8', 'Ancho del campo NUMERO_REGISTROS de la cola.'),
              ('REPORTE_ANCHO_SUMATORIA_NOVEDADES', '8', 'Ancho del campo SUMATORIA_NOVEDADES de la cola.'),
              ('REPORTE_COLA_ANCHO_FILLER', '758', 'Ancho del filler de relleno de la cola. Debe ser 800 menos la suma de todos los demás campos de la cola (hoy 42).')
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DELETE FROM datacredito.configuracion WHERE codigo IN (
              'REPORTE_ANCHO_LINEA', 'REPORTE_CAB_REGISTRO_INICIAL', 'REPORTE_ANCHO_FECHA',
              'REPORTE_CAB_AMPLIACION_MILENIO', 'REPORTE_CAB_INDICADOR_VALORES_MILES', 'REPORTE_CAB_TIPO_ENTREGA',
              'REPORTE_CAB_FECHA_INICIO_REPORTE', 'REPORTE_CAB_FECHA_FIN_REPORTE', 'REPORTE_CAB_INDICADOR_PARTIR',
              'REPORTE_CAB_ANCHO_FILLER', 'REPORTE_ANCHO_TIPO_IDENTIFICACION', 'REPORTE_ANCHO_NUMERO_IDENTIFICACION',
              'REPORTE_ANCHO_NUMERO_CUENTA_OBLIGACION', 'REPORTE_ANCHO_NOMBRE_COMPLETO', 'REPORTE_LARGO_MAXIMO_NOMBRE_COMPLETO',
              'REPORTE_ANCHO_RESPONSABLE_CALIDAD_DEUDOR', 'REPORTE_ANCHO_NOMBRE_MARCA_PRIVACIDAD', 'REPORTE_ANCHO_CALIFICACION',
              'REPORTE_ANCHO_PROBABILIDAD_INCUMPLIMIENTO', 'REPORTE_ANCHO_EDAD_INCUMPLIMIENTO', 'REPORTE_ANCHO_VALOR_MONETARIO',
              'REPORTE_ANCHO_CUOTAS', 'REPORTE_ANCHO_CIUDAD', 'REPORTE_ANCHO_CODIGO_DANE',
              'REPORTE_ANCHO_DIRECCION', 'REPORTE_ANCHO_TELEFONO', 'REPORTE_ANCHO_CORREO',
              'REPORTE_ANCHO_SUSCRIPTOR_DESTINO', 'REPORTE_ANCHO_NUMERO_TARJETA', 'REPORTE_COLA_IDENTIFICADOR',
              'REPORTE_ANCHO_NUMERO_REGISTROS', 'REPORTE_ANCHO_SUMATORIA_NOVEDADES', 'REPORTE_COLA_ANCHO_FILLER'
            )
            SQL);
    }
}
