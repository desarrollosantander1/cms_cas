<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Mueve el texto de las cartas jurídicas PDF (data_credito.html.twig /
 * prejuridico.html.twig) de párrafos quemados en Twig a
 * datacredito.configuracion, siguiendo el mismo patrón ya usado para el
 * correo (CARTAS_MENSAJE_PIE/CARTAS_ASUNTO_BASE/CARTAS_TITULO_CORREO de
 * Version20260916120000): así un cambio de redacción ya no requiere
 * despliegue. Los fragmentos que dependen de datos de la generación
 * (plazo de pago, fecha de mora, correo del asesor) guardan un placeholder
 * literal ({PLAZO_PAGO_DIAS} / {FECHA_MORA_DESDE} / {EMAIL_ASESOR}) que
 * ArmadorCartaService::textosCarta() resuelve antes de pasar el texto (ya
 * HTML) a Twig — ver ConfiguracionService::textosCartaDataCredito() /
 * textosCartaPrejuridico().
 *
 * Amplía primero `valor` a VARCHAR sin longitud fija: el párrafo legal más
 * largo (CARTA_PREJURIDICO_PARRAFO_PLAZO_LEGAL) supera los 500 caracteres
 * del límite original.
 */
final class Version20260918120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Amplía datacredito.configuracion.valor y siembra los textos editables del cuerpo de las cartas jurídicas PDF';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE datacredito.configuracion ALTER COLUMN valor TYPE VARCHAR');

        $this->addSql(<<<'SQL'
            INSERT INTO datacredito.configuracion (codigo, valor, descripcion) VALUES
              ('CARTA_DATACREDITO_REFERENCIA', 'Referencia: Reporte central de riesgo financiero.', 'Carta Data Crédito PDF: línea de referencia.'),
              ('CARTA_DATACREDITO_SALUDO', 'Estimado(a) cliente.', 'Carta Data Crédito PDF: saludo.'),
              ('CARTA_DATACREDITO_INTRO', 'Para nosotros es importante contar con usted como un aliado comercial, le recordamos que cuenta con un portafolio de productos y servicios hecho a su medida, su satisfacción es nuestra prioridad.', 'Carta Data Crédito PDF: párrafo de introducción.'),
              ('CARTA_DATACREDITO_PARRAFO_PLAZO', 'Respetuosamente invitamos que se revise el tiempo de cancelación de las facturas, ya que hemos visto que en ocasiones no se acomodan al plazo de crédito aprobado con nuestra compañía <mark>({PLAZO_PAGO_DIAS} días)</mark>.', 'Carta Data Crédito PDF: párrafo de plazo de pago. Placeholder {PLAZO_PAGO_DIAS} = plazo derivado de las facturas seleccionadas.'),
              ('CARTA_DATACREDITO_PARRAFO_LEY1266', 'De conformidad con el artículo 12 de la Ley 1266 de 2008, nos permitimos informarle que, en nuestra calidad de fuente de información, reportaremos el incumplimiento de la cartera vencida, al respectivo banco de información financiera; a la fecha presenta mora desde el {FECHA_MORA_DESDE} en concordancia con la(s) siguiente(s) factura(s) cambiaras de venta.', 'Carta Data Crédito PDF: párrafo legal Ley 1266. Placeholder {FECHA_MORA_DESDE} = fecha de vencimiento más antigua de las facturas seleccionadas.'),
              ('CARTA_DATACREDITO_PARRAFO_CUMPLIMIENTO', 'Lo anterior en cumplimiento del mandato legal establecido en el inciso 2 del artículo 12 de la Ley 1266 de 2008, esto es requisito especial para las fuentes.', 'Carta Data Crédito PDF: párrafo de cumplimiento legal.'),
              ('CARTA_DATACREDITO_PARRAFO_DINAMICA', 'Es importante tener en cuenta que para que la dinámica comercial opere de manera oportuna no deban presentarse moras en las facturas.', 'Carta Data Crédito PDF: párrafo de cierre comercial.'),
              ('CARTA_DATACREDITO_CUENTAS_BANCARIAS', 'Recordamos las entidades bancarias y las respectivas cuentas corrientes en las que pueden realizar los pagos:<br>Banco de Bogotá: 46811116-6 &nbsp;&nbsp; Bancolombia: 1150586670-8 &nbsp;&nbsp; Davivienda: 30299597-2', 'Carta Data Crédito PDF: cuentas bancarias para el pago.'),
              ('CARTA_DATACREDITO_CONTACTO', 'Cualquier duda o aclaración, con gusto se atenderá en el teléfono (606) 3400402 extensión 103', 'Carta Data Crédito PDF: línea de contacto telefónico.'),
              ('CARTA_PREJURIDICO_ASUNTO', 'Asunto: Solicitud pago facturas pendientes', 'Carta Prejurídico PDF (con/sin interés): línea de asunto.'),
              ('CARTA_PREJURIDICO_REFERENCIA', 'Referencia: Facturación en mora.', 'Carta Prejurídico PDF (con/sin interés): línea de referencia.'),
              ('CARTA_PREJURIDICO_SALUDO', 'Estimado(a) cliente.', 'Carta Prejurídico PDF (con/sin interés): saludo.'),
              ('CARTA_PREJURIDICO_INTRO', 'Nos permitimos informar que, a la fecha y de acuerdo a los estados de cartera consolidados, se evidencia por parte de Ustedes, mora en el pago de las siguientes facturas.', 'Carta Prejurídico PDF (con/sin interés): párrafo de introducción.'),
              ('CARTA_PREJURIDICO_PARRAFO_PLAZO_LEGAL', 'Teniendo en cuenta que en diversas ocasiones se ha contactado nuestro asesor comercial y el área de Cartera con el ánimo de lograr un acuerdo de pago y ha sido infructuoso, les notifico que a partir de la fecha, concedemos un último plazo de ocho (08) días calendario, contados a partir del recibo de esta comunicación, para que se proceda a efectuar la totalidad del pago; por tanto, si a la fecha antes mencionada no ha sido reportada dicha cancelación, entenderemos su falta de interés conciliatoria e iniciaremos los trámites judiciales a que haya lugar, instaurando para tal fin una acción legal (demanda), que le generará honorarios profesionales de abogado, pago de intereses y costos judiciales.', 'Carta Prejurídico PDF (con/sin interés): párrafo legal de último plazo y advertencia de acción jurídica.'),
              ('CARTA_PREJURIDICO_CUENTAS_BANCARIAS', 'A continuación, relaciono las cuentas corrientes donde se puede realizar el pago correspondiente:<br>Bancolombia 11505866708, Banco Bogotá 46811116-6 cuenta corriente.', 'Carta Prejurídico PDF (con/sin interés): cuentas bancarias para el pago.'),
              ('CARTA_PREJURIDICO_PARRAFO_PAGO', 'Agradecemos que, al momento de realizar el pago, se envíe copia de la consignación al correo:<br><b>{EMAIL_ASESOR}</b> y/o comunicarse al número telefónico: 3235656454 - 3137400396', 'Carta Prejurídico PDF (con/sin interés): párrafo de confirmación de pago. Placeholder {EMAIL_ASESOR} = correo del asesor que genera la carta.'),
              ('CARTAS_DESPEDIDA', 'Cordialmente,', 'Cartas jurídicas PDF (Data Crédito y Prejurídico): línea de despedida, compartida entre ambas plantillas.')
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DELETE FROM datacredito.configuracion WHERE codigo IN (
              'CARTA_DATACREDITO_REFERENCIA', 'CARTA_DATACREDITO_SALUDO', 'CARTA_DATACREDITO_INTRO',
              'CARTA_DATACREDITO_PARRAFO_PLAZO', 'CARTA_DATACREDITO_PARRAFO_LEY1266', 'CARTA_DATACREDITO_PARRAFO_CUMPLIMIENTO',
              'CARTA_DATACREDITO_PARRAFO_DINAMICA', 'CARTA_DATACREDITO_CUENTAS_BANCARIAS', 'CARTA_DATACREDITO_CONTACTO',
              'CARTA_PREJURIDICO_ASUNTO', 'CARTA_PREJURIDICO_REFERENCIA', 'CARTA_PREJURIDICO_SALUDO',
              'CARTA_PREJURIDICO_INTRO', 'CARTA_PREJURIDICO_PARRAFO_PLAZO_LEGAL', 'CARTA_PREJURIDICO_CUENTAS_BANCARIAS',
              'CARTA_PREJURIDICO_PARRAFO_PAGO', 'CARTAS_DESPEDIDA'
            )
            SQL);
        $this->addSql('ALTER TABLE datacredito.configuracion ALTER COLUMN valor TYPE VARCHAR(500)');
    }
}
