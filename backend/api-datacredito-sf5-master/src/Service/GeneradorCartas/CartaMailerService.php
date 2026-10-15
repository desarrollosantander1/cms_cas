<?php

namespace App\Service\GeneradorCartas;

use App\Service\ConfiguracionService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

/**
 * Envía la carta jurídica por correo con el PDF adjunto. Portado del
 * GeneradorCartasController::sendEmail() del legacy:
 * - asunto por tipo (ya en TipoCartaJuridica::asuntoCorreo())
 * - cuerpo generador_cartas/email/index.html.twig
 * - BCC al asesor logueado
 *
 * `$from` = %env(MAILER_FROM)% — con Office365 debe ser el buzón autenticado
 * del DSN o un alias autorizado, si no el servidor rechaza el envío.
 */
final class CartaMailerService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly ConfiguracionService $configuraciones,
        private readonly string $from,
        private readonly string $assetsDir,
    ) {
    }

    public function enviar(TipoCartaJuridica $tipo, string $pdfBytes, string $destinatario, ?string $bccAsesor): void
    {
        $destinatario = trim($destinatario);
        if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            throw new BadRequestHttpException(sprintf('El correo del destinatario no es válido: "%s".', $destinatario));
        }

        $asunto = $tipo->asuntoCorreo($this->configuraciones->cartasAsuntoBase());

        $html = $this->twig->render('generador_cartas/email/index.html.twig', [
            'title' => $this->configuraciones->cartasTituloCorreo(),
            'mensaje' => $this->configuraciones->cartasMensajePie(),
            'asunto' => $asunto,
        ]);

        $email = (new Email())
            ->from($this->from)
            ->to($destinatario)
            ->subject($asunto)
            ->html($html)
            ->attach($pdfBytes, $tipo->nombreArchivo(), 'application/pdf');

        $logo = $this->assetsDir.'/logo2.jpg';
        if (is_file($logo)) {
            $email->embedFromPath($logo, 'logo', 'image/jpeg');
        }

        if ($bccAsesor !== null && filter_var($bccAsesor, FILTER_VALIDATE_EMAIL)) {
            $email->addBcc($bccAsesor);
        }

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            throw new HttpException(502, 'No se pudo enviar el correo: '.$e->getMessage(), $e);
        }
    }
}
