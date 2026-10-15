<?php

namespace App\Service;

use App\Entity\Orden;
use Mpdf\Mpdf;
use Twig\Environment;

/**
 * Twig -> HTML -> PDF (mPDF) de la "Solicitud de Reparación" que se adjunta al correo enviado
 * al cliente al crear una orden — ver OrdenMailerService. Mismo patrón que
 * API datacredito sf4-1/src/Service/GeneradorCartas/CartaPdfService.php: mPDF es 100% PHP (sin
 * binario externo tipo wkhtmltopdf, no hace falta tocar la imagen Docker compartida).
 *
 * `assetsDir` = ruta absoluta a public/orden-pdf (mPDF lee logo2.jpg directamente del disco, no
 * por URL).
 */
final class OrdenPdfService
{
    public function __construct(
        private readonly Environment $twig,
        private readonly string $assetsDir,
        private readonly string $tempDir,
    ) {
    }

    /** @return string bytes del PDF */
    public function generar(Orden $orden): string
    {
        if (!is_dir($this->tempDir)) {
            @mkdir($this->tempDir, 0775, true);
        }

        $html = $this->twig->render('orden/pdf.html.twig', [
            'orden' => $orden,
            'assetsDir' => $this->assetsDir,
        ]);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => $this->tempDir,
            // Mismos márgenes que el @page de templates/orden/pdf.html.twig — ajustados para
            // que no quede espacio vacío de más entre el encabezado (logo + datos del CAS) y el
            // contenido de la orden.
            'margin_top' => 30,
            'margin_bottom' => 16,
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_header' => 8,
            'margin_footer' => 8,
        ]);
        $mpdf->SetTitle(sprintf('Solicitud de reparación %s', $orden->getId()));
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }
}
