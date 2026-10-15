<?php

namespace App\Service\Reporte;

/**
 * Layout físico completo del plano de ancho fijo que se sube a DataCrédito:
 * anchos de cada campo, literales fijos de cabecera/cola y los fillers.
 * Antes vivían como `private const` de GeneradorTxtService; ahora se leen
 * de datacredito.configuracion (prefijo REPORTE_*) para que puedan
 * ajustarse sin desplegar código si DataCrédito cambia el formato — ver
 * ConfiguracionService::layoutPlanoTxt(), único punto que arma este objeto
 * con los valores vigentes.
 *
 * Value object de solo datos, sin comportamiento: GeneradorTxtService sigue
 * siendo el único punto que sabe cómo se usan estos anchos (padding,
 * armado de línea, validación de 800 caracteres).
 *
 * ADVERTENCIA: estos valores son el contrato físico del archivo que exige
 * DataCrédito, no un parámetro de negocio ajustable a discreción — cambiar
 * un ancho sin que el resto de la fila (y los fillers) se recalculen
 * desplaza todas las posiciones de campo del plano. Cualquier edición debe
 * validarse regenerando un plano contra un período real y comparando
 * byte a byte contra la especificación de DataCrédito.
 */
final readonly class LayoutPlanoTxt
{
    public function __construct(
        public int $anchoLinea,
        public string $cabRegistroInicial,
        public int $anchoFecha,
        public string $cabAmpliacionMilenio,
        public string $cabIndicadorValoresMiles,
        public string $cabTipoEntrega,
        public string $cabFechaInicioReporte,
        public string $cabFechaFinReporte,
        public string $cabIndicadorPartir,
        public int $cabAnchoFiller,
        public int $anchoTipoIdentificacion,
        public int $anchoNumeroIdentificacion,
        public int $anchoNumeroCuentaObligacion,
        public int $anchoNombreCompleto,
        public int $largoMaximoNombreCompleto,
        public int $anchoResponsableCalidadDeudor,
        public int $anchoNombreMarcaPrivacidad,
        public int $anchoCalificacion,
        public int $anchoProbabilidadIncumplimiento,
        public int $anchoEdadIncumplimiento,
        public int $anchoValorMonetario,
        public int $anchoCuotas,
        public int $anchoCiudad,
        public int $anchoCodigoDane,
        public int $anchoDireccion,
        public int $anchoTelefono,
        public int $anchoCorreo,
        public int $anchoSuscriptorDestino,
        public int $anchoNumeroTarjeta,
        public string $colaIdentificador,
        public int $anchoNumeroRegistros,
        public int $anchoSumatoriaNovedades,
        public int $colaAnchoFiller,
    ) {
    }
}
