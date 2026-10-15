<?php

namespace App\Service;

use App\Entity\Gbl\AdjuntoTipo;
use App\Repository\Gbl\AdjuntoTipoRepository;

/**
 * URL de la foto de catálogo de un producto: base_url + directorio de gbl.adjunto_tipo ("Fotos
 * producto") + item + "_1.jpg" (ej. item "299902771" -> ".../productos/299902771_1.jpg").
 * Antes vivía duplicada como método privado de ProductoController; ahora también la usa
 * OrdenController::serializeOrden() para mostrar la imagen en el detalle/edición de una orden.
 *
 * No se verifica contra el servidor remoto que el archivo realmente exista — el frontend cae a
 * una imagen por defecto si esta no carga (evento "error" de la imagen).
 */
final class ProductoImagenService
{
    private const ADJUNTO_TIPO_FOTOS_PRODUCTO = 'Fotos producto';

    public function __construct(
        private readonly AdjuntoTipoRepository $adjuntoTipos,
    ) {
    }

    /** Buscar una sola vez por request y reusar en cada llamada a url() — evita un N+1 al armar la URL de varios productos (ej. un typeahead). */
    public function buscarAdjuntoTipo(): ?AdjuntoTipo
    {
        return $this->adjuntoTipos->findOneBy(['descripcion' => self::ADJUNTO_TIPO_FOTOS_PRODUCTO]);
    }

    public function url(string $item, ?AdjuntoTipo $adjuntoTipo): ?string
    {
        if ($adjuntoTipo === null || $adjuntoTipo->getDirectorio() === null) {
            return null;
        }

        return $adjuntoTipo->getBaseUrl().trim($adjuntoTipo->getDirectorio(), '/').'/'.rawurlencode($item).'_1.jpg';
    }
}
