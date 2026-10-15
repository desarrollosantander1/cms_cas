<?php

namespace App\Service\GeneradorCartas;

use App\Service\ConfiguracionService;
use Mpdf\Mpdf;
use Twig\Environment;

/**
 * Twig -> HTML -> PDF (mPDF). Reemplaza al knp-snappy/wkhtmltopdf del plan
 * rev 2: wkhtmltopdf ya no está en los repos de Alpine, y mPDF es 100% PHP
 * (sin binario ni rebuild de la imagen compartida). Las plantillas se
 * reescribieron a layout de tablas + htmlpageheader/footer nativos de mPDF.
 *
 * `assetsDir` = ruta absoluta a public/generador-cartas (mPDF lee firma.png /
 * logo2.jpg / logo4.png directamente del disco, no por URL).
 */
final class CartaPdfService
{
    public function __construct(
        private readonly Environment $twig,
        private readonly ConfiguracionService $configuraciones,
        private readonly string $assetsDir,
        private readonly string $tempDir,
    ) {
    }

    /**
     * @param array<string, mixed> $contexto contexto de ArmadorCartaService
     *
     * @return string bytes del PDF
     */
    public function generar(TipoCartaJuridica $tipo, array $contexto): string
    {
        if (!is_dir($this->tempDir)) {
            @mkdir($this->tempDir, 0775, true);
        }

        $html = $this->twig->render($tipo->plantilla(), $contexto + ['assetsDir' => $this->assetsDir]);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => $this->tempDir,
            'margin_top' => 40,
            'margin_bottom' => 26,
            'margin_left' => 18,
            'margin_right' => 18,
            'margin_header' => 8,
            'margin_footer' => 8,
        ]);
        $mpdf->SetTitle($tipo->asuntoCorreo($this->configuraciones->cartasAsuntoBase()));
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }
}
