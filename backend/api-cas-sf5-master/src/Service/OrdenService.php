<?php

namespace App\Service;

use App\Entity\Cas;
use App\Entity\Gbl\Producto;
use App\Entity\Gbl\Usuario;
use App\Entity\Orden;
use App\Entity\OrdenAdjunto;
use App\Entity\OrdenEstadoLog;
use App\Repository\Gbl\AdjuntoTipoRepository;
use App\Repository\Gbl\ProductoRepository;
use App\Repository\OrdenEstadoRepository;
use App\Repository\OrdenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Orquesta la creación de una orden de servicio: valida producto y garantía
 * (punto 2 del encargo — delegado en ProductoGarantiaService), encuentra o
 * crea el cliente final del CAS (punto 1 — delegado en CasClienteService),
 * asigna el siguiente número secuencial DE ESTE CAS y guarda los adjuntos
 * que vengan. Todo en una sola transacción: si algo falla (ej. un archivo
 * con tipo no permitido) no debe quedar ninguna fila a medias.
 */
class OrdenService
{
    private const ESTADO_INICIAL = 'ORDEN CREADA';
    private const ADJUNTO_TIPO_ORDEN = 'Adjuntos ordenes de servicio';

    public function __construct(
        private readonly ProductoRepository $productos,
        private readonly ProductoGarantiaService $garantiaService,
        private readonly CasClienteService $casClienteService,
        private readonly OrdenRepository $ordenes,
        private readonly OrdenEstadoRepository $ordenEstados,
        private readonly AdjuntoTipoRepository $adjuntoTipos,
        private readonly ArchivoAdjuntoUploader $uploader,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @param array<string, mixed> $datos
     * @param UploadedFile[] $archivos
     */
    public function crear(Cas $cas, Usuario $usuario, array $datos, array $archivos): Orden
    {
        $producto = $this->productos->find((int) ($datos['productoId'] ?? 0));
        if ($producto === null || !$producto->isEstado()) {
            throw new NotFoundHttpException('Producto no encontrado.');
        }

        $fechaFactura = $this->parsearFecha($datos['fechaFactura'] ?? null);

        $garantia = $this->garantiaService->validar($producto, $fechaFactura);
        if (!$garantia['tieneGarantia']) {
            throw new ConflictHttpException('Este producto no tiene garantía configurada.');
        }
        if ($garantia['vigente'] !== true) {
            throw new ConflictHttpException(sprintf('La garantía de este producto expiró el %s.', $garantia['fechaLimite']->format('Y-m-d')));
        }

        $reporteCliente = trim((string) ($datos['reporteCliente'] ?? ''));
        if ($reporteCliente === '') {
            throw new BadRequestHttpException('El reporte del cliente es requerido.');
        }

        return $this->em->wrapInTransaction(
            fn () => $this->guardar($cas, $usuario, $producto, $fechaFactura, $reporteCliente, $datos, $archivos)
        );
    }

    /** @param array<string, mixed> $datos @param UploadedFile[] $archivos */
    private function guardar(
        Cas $cas,
        Usuario $usuario,
        Producto $producto,
        \DateTimeImmutable $fechaFactura,
        string $reporteCliente,
        array $datos,
        array $archivos,
    ): Orden {
        $datosCliente = [
            'cedula' => $datos['cedula'] ?? '',
            'nombre' => $datos['nombreCliente'] ?? '',
            'apellido' => $datos['apellidoCliente'] ?? '',
            'telefono' => $datos['telefonoCliente'] ?? '',
        ];
        // A diferencia de los anteriores (siempre requeridos), correo/dirección son opcionales: si esta orden no los
        // trae, no se deben borrar los que el cliente ya tenía guardados de una orden anterior — ver CasClienteService,
        // que solo toca estos dos campos cuando la clave está presente.
        if (array_key_exists('correoCliente', $datos)) {
            $datosCliente['correo'] = $datos['correoCliente'];
        }
        if (array_key_exists('direccionCliente', $datos)) {
            $datosCliente['direccion'] = $datos['direccionCliente'];
        }

        $casCliente = $this->casClienteService->encontrarOCrear($cas, $datosCliente);

        $ordenEstado = $this->ordenEstados->findOneBy(['descripcion' => self::ESTADO_INICIAL]);
        if ($ordenEstado === null) {
            throw new ConflictHttpException(sprintf('El estado inicial "%s" no existe en cas.orden_estado.', self::ESTADO_INICIAL));
        }

        // El lock se toma DENTRO de la transacción (wrapInTransaction) y se libera automáticamente al terminarla.
        $this->ordenes->bloquearNumeracion($cas);
        $numero = $this->ordenes->siguienteNumero($cas);

        $orden = new Orden($cas, $casCliente, $ordenEstado, $usuario, $producto, $fechaFactura, $numero);
        $numeroFactura = trim((string) ($datos['numeroFactura'] ?? ''));
        $orden->setNumeroFactura($numeroFactura !== '' ? $numeroFactura : null);
        $descripcionCompra = trim((string) ($datos['descripcionCompra'] ?? ''));
        $orden->setDescripcionCompra($descripcionCompra !== '' ? $descripcionCompra : null);
        $orden->setReporteCliente($reporteCliente);

        $tipoIngreso = (int) ($datos['tipoIngreso'] ?? 1);
        $orden->setTipoIngreso($tipoIngreso);
        if ($tipoIngreso !== 1) {
            $ordenRelaciona = trim((string) ($datos['ordenRelaciona'] ?? ''));
            $orden->setOrdenRelaciona($ordenRelaciona !== '' ? $ordenRelaciona : null);
        }

        $this->em->persist($orden);
        $this->em->flush(); // necesita el id de la orden para el subdirectorio de sus adjuntos

        // Primer registro de trazabilidad de cas.orden_estado_log: deja constancia de que esta
        // orden nació con self::ESTADO_INICIAL y quién la creó (ver OrdenEstadoLog).
        $this->em->persist(new OrdenEstadoLog($orden, $ordenEstado, $usuario));

        if ($archivos !== []) {
            // Único tipo de adjunto de orden que existe hoy (ver Version20260924203625) — el
            // formulario no pide categorizar cada archivo, así que se asigna este mismo a todos.
            $adjuntoTipo = $this->adjuntoTipos->findOneBy(['descripcion' => self::ADJUNTO_TIPO_ORDEN]);
            if ($adjuntoTipo === null) {
                throw new ConflictHttpException(sprintf('El tipo de adjunto "%s" no existe en gbl.adjunto_tipo.', self::ADJUNTO_TIPO_ORDEN));
            }
            if ($adjuntoTipo->getDirectorio() === null) {
                throw new ConflictHttpException(sprintf('El tipo de adjunto "%s" no tiene "directorio" configurado en gbl.adjunto_tipo.', self::ADJUNTO_TIPO_ORDEN));
            }

            // Tope por archivo Y tope por la SUMA de los archivos de esta orden, ambos configurables
            // por CAS (ver Cas::getMaximoMbArchivo()/getMaximoMbOrden() y Version20261009203340) —
            // ya no son constantes fijas de ArchivoAdjuntoUploader. El de la suma se valida ANTES de
            // mover ningún archivo: si se excede, que falle rápido y no deje adjuntos físicos a
            // medias (el rollback de la transacción no alcanza al filesystem).
            $mimePermitidos = $cas->getMimePermitidosLista();
            $tamanoMaximoArchivoBytes = (int) round((float) $cas->getMaximoMbArchivo() * 1024 * 1024);
            $tamanoMaximoOrdenBytes = (int) round((float) $cas->getMaximoMbOrden() * 1024 * 1024);

            $tamanoTotal = array_sum(array_map(static fn (UploadedFile $a) => $a->getSize() ?? 0, $archivos));
            if ($tamanoTotal > $tamanoMaximoOrdenBytes) {
                throw new BadRequestHttpException(sprintf('Los archivos adjuntos superan el tamaño máximo permitido por orden (%s MB).', $cas->getMaximoMbOrden()));
            }

            // El subdirectorio físico cuelga de adjunto_tipo.path_adjunto (ej. "public/uploads/cas") y
            // se completa con el cas y la orden (ofuscados, ver ArchivoAdjuntoUploader::subdirectorioOrden())
            // y el directorio propio del tipo: no se arma ninguna ruta fija aquí. Una sola carpeta
            // por orden (no una por archivo): todos sus adjuntos caen ahí, y es el uploader el que
            // evita que dos con el mismo nombre original (ej. dos "foto.jpg") se pisen entre sí.
            $subdirectorio = $this->uploader->subdirectorioOrden($cas->getId(), $adjuntoTipo->getDirectorio(), $orden->getId());
            foreach ($archivos as $archivo) {
                $guardado = $this->uploader->guardar($archivo, $mimePermitidos, $tamanoMaximoArchivoBytes, $adjuntoTipo->getPathAdjunto(), $subdirectorio);

                $ordenAdjunto = new OrdenAdjunto($orden, $guardado['nombre'], $guardado['ruta']);
                $ordenAdjunto->setExtension($guardado['extension']);
                $ordenAdjunto->setAdjuntoTipo($adjuntoTipo);
                $this->em->persist($ordenAdjunto);
            }
        }

        $this->em->flush();

        return $orden;
    }

    /**
     * `diagnostico`/`reporteFinal` se habilitan DESPUÉS de creada la orden (ver
     * Version20261012192602) — a diferencia de crear(), acá no hay transacción ni validaciones de
     * negocio que orquestar, por eso no hace falta pasar por wrapInTransaction/guardar(): un
     * UPDATE de dos columnas no necesita más que el flush de abajo.
     *
     * @param array<string, mixed> $datos
     */
    public function actualizar(Orden $orden, array $datos): Orden
    {
        if (array_key_exists('diagnostico', $datos)) {
            $diagnostico = trim((string) ($datos['diagnostico'] ?? ''));
            $orden->setDiagnostico($diagnostico !== '' ? $diagnostico : null);
        }
        if (array_key_exists('reporteFinal', $datos)) {
            $reporteFinal = trim((string) ($datos['reporteFinal'] ?? ''));
            $orden->setReporteFinal($reporteFinal !== '' ? $reporteFinal : null);
        }

        $this->em->flush();

        return $orden;
    }

    private function parsearFecha(mixed $valor): \DateTimeImmutable
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            throw new BadRequestHttpException('La fecha de factura es requerida.');
        }
        try {
            return new \DateTimeImmutable($valor);
        } catch (\Exception) {
            throw new BadRequestHttpException('Fecha de factura inválida.');
        }
    }
}
