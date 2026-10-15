<?php

namespace App\Service;

use App\Entity\Orden;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

/**
 * Envía al cliente de una orden recién creada el correo de "solicitud de reparación recibida",
 * con el PDF de OrdenPdfService adjunto. Adaptado de .scratch/cas/email-orden/logica.php, con el
 * mismo patrón de envío (adjunto + logo embebido) que
 * API datacredito sf4-1/src/Service/GeneradorCartas/CartaMailerService.php.
 *
 * El caller (OrdenController) decide CUÁNDO llamar a `enviar()` — el disparo es a pedido del
 * frontend tras crear la orden, no automático desde OrdenService, para no acoplar la creación de
 * la orden (que debe quedar guardada aunque el correo falle) al envío del correo.
 *
 * `$from` = %env(MAILER_FROM)% — con un proveedor real (ej. Office365) debe ser el buzón
 * autenticado del DSN o un alias autorizado, si no el servidor rechaza el envío.
 */
final class OrdenMailerService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly OrdenPdfService $pdf,
        private readonly string $from,
        private readonly string $assetsDir,
    ) {
    }

    public function enviar(Orden $orden): void
    {
        $destinatario = trim((string) $orden->getCasCliente()->getCorreo());
        if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            throw new BadRequestHttpException(sprintf('El correo del cliente no es válido: "%s".', $destinatario));
        }

        $asunto = 'Solicitud de reparación recibida';
        $htmlBody = $this->twig->render('orden/email/cuerpo.html.twig', [
            'cuerpo' => $this->construirCuerpo($orden),
        ]);
        
        $pdfBytes = $this->pdf->generar($orden);

        $email = (new Email())
            ->from($this->from)
            ->to($destinatario)
            ->subject($asunto)
            ->html($htmlBody)
            ->attach($pdfBytes, sprintf('solicitud-reparacion-%s.pdf', $orden->getId()), 'application/pdf');

        $logo = $this->assetsDir.'/logo2.jpg';
        if (is_file($logo)) {
            $email->embedFromPath($logo, 'logo', 'image/jpeg');
        }

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            throw new HttpException(502, 'No se pudo enviar el correo: '.$e->getMessage(), $e);
        }
    }

    private function construirCuerpo(Orden $orden): string
    {
        $cliente   = $orden->getCasCliente();
        $cas       = $orden->getCas();
        $ciudad    = $cas->getCiudad()?->getNombre() ?? $cas->getClienteSucursal()?->getCiudadNombre();

        return sprintf(
            'Estimado(a) %s %s, se ha creado la Orden de Reparación N° %s en el Centro Autorizado de Servicio %s%s, '
                .'atendiendo a su solicitud de Garantía del producto Home Elements %s. En el transcurso de diez (10) días '
                .'hábiles el Centro Autorizado de Servicio estará dando respuesta a su solicitud.',
            $cliente->getNombre(),
            $cliente->getApellido(),
            $orden->getId(),
            $cas->nombreMostrar(),
            $ciudad !== null ? ', ubicado en '.$ciudad : '',
            $orden->getProducto()->getDescripcion() ?? $orden->getProducto()->getItem(),
        );
    }
}
