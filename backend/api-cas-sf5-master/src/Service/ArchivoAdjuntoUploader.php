<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Valida y sube un archivo a {kernel.project_dir}/{pathAdjunto}/{subdirectorio}/,
 * donde `pathAdjunto` viene de gbl.adjunto_tipo.path_adjunto (ej.
 * "public/uploads/cas") — no se quema ninguna ruta aquí, el caller la resuelve
 * desde el catálogo según el tipo de adjunto. cas-nginx ya sirve public/ como
 * root con `try_files $uri ...` (ver docker/nginx/cas.conf), así que mientras
 * `pathAdjunto` caiga bajo public/ queda descargable como estático sin tocar
 * la configuración de nginx.
 *
 * `mimePermitidos`/`tamanoMaximoBytes` ya no son constantes fijas: el caller
 * los resuelve por CAS (ver Cas::getMimePermitidosLista()/getMaximoMbArchivo()
 * y Version20261009203340) — cada centro de servicio puede tener su propia
 * lista de formatos y su propio tope de tamaño por archivo.
 *
 * El archivo NO se guarda con su nombre original: se genera un nombre propio
 * (hex aleatorio) para el archivo físico, deliberadamente distinto del
 * nombre con el que se subió — así nunca hay colisión entre adjuntos de una
 * misma orden (ej. dos fotos de cámara llamadas "foto.jpg") ni problemas con
 * caracteres raros en el nombre original. El caller persiste el nombre
 * original en `nombre` (para mostrárselo al usuario) y el nombre generado en
 * `ruta` (para poder armar la URL de descarga en tiempo de consulta con
 * adjuntoTipo.baseUrl/directorio + el cas/orden + esa `ruta` — ver
 * OrdenController::urlAdjunto() y Version20261009133021).
 *
 * Genérico a propósito: hoy solo lo usa OrdenService para los adjuntos de una
 * orden, pero no depende de esa entidad — reutilizable a futuro (ej.
 * gbl.producto_adjunto).
 *
 * `subdirectorioOrden()` centraliza cómo se ofusca el cas/orden dentro de la
 * ruta física (cas_id en md5, orden_id en sha256): tanto quien guarda el
 * archivo (OrdenService) como quien arma la URL de descarga
 * (OrdenController::urlAdjunto()) deben calcular EXACTAMENTE la misma ruta,
 * así que viven en un solo lugar en vez de reimplementarse en cada caller.
 * No es cifrado ni control de acceso real — la autorización sigue
 * dependiendo de `casYUsuarioAutorizado()`; esto solo evita que alguien
 * enumere archivos ajenos navegando carpetas con ids secuenciales.
 */
class ArchivoAdjuntoUploader
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
    }

    /** Subdirectorio físico/de URL de una orden dentro de su tipo de adjunto: cas ofuscado + el directorio propio del tipo (ej. "orden-adjuntos") + orden ofuscada. */
    public function subdirectorioOrden(int $casId, string $directorioTipo, int $ordenId): string
    {
        return self::casIdCodificado($casId).'/'.trim($directorioTipo, '/').'/'.self::ordenIdHash($ordenId);
    }

    public static function casIdCodificado(int $casId): string
    {
        return md5((string) $casId);
    }

    public static function ordenIdHash(int $ordenId): string
    {
        return hash('sha256', (string) $ordenId);
    }

    /**
     * @param string[] $mimePermitidos
     * @return array{nombre: string, ruta: string, extension: ?string} `nombre` es el nombre
     *               original tal como se subió (para mostrárselo al usuario); `ruta` es el nombre
     *               generado con el que quedó guardado en disco, el que el caller debe persistir
     *               en su columna `ruta`.
     */
    public function guardar(UploadedFile $archivo, array $mimePermitidos, int $tamanoMaximoBytes, string $pathAdjunto, string $subdirectorio): array
    {
        $nombreOriginal = $archivo->getClientOriginalName();
        $mimeType       = $archivo->getMimeType();
        $tamano         = $archivo->getSize();
        $extension      = $archivo->guessExtension() ?: ($archivo->getClientOriginalExtension() ?: null);

        if (!in_array($mimeType, $mimePermitidos, true)) {
            throw new BadRequestHttpException(sprintf('Tipo de archivo no permitido: "%s" (%s).', $nombreOriginal, $mimeType));
        }
        if ($tamano !== null && $tamano > $tamanoMaximoBytes) {
            throw new BadRequestHttpException(sprintf('El archivo "%s" supera el tamaño máximo permitido (%s MB).', $nombreOriginal, rtrim(rtrim(number_format($tamanoMaximoBytes / (1024 * 1024), 2, '.', ''), '0'), '.')));
        }

        $pathAdjunto = trim($pathAdjunto, '/');
        $subdirectorio = trim($subdirectorio, '/');

        $nombreDestino = bin2hex(random_bytes(16)).($extension !== null ? '.'.$extension : '');
        $archivo->move($this->projectDir.'/'.$pathAdjunto.'/'.$subdirectorio, $nombreDestino);

        return [
            'nombre' => $nombreOriginal,
            'ruta' => $nombreDestino,
            'extension' => $extension,
        ];
    }
}
