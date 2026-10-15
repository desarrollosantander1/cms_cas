<?php

namespace App\Service;

use App\Entity\Gbl\Producto;
use App\Repository\Gbl\ProductoCalidadRepository;

/**
 * Única fuente de verdad de la regla de garantía de un producto (punto 2 del
 * encargo de creación de orden): la garantía vive en gbl.producto_calidad,
 * localizada por gbl.producto_calidad_tipo.descripcion = "GARANTIA" (ver
 * ProductoCalidadRepository::buscarGarantia()); la unidad (gbl.unidad_medida)
 * dice si `valor` son días, meses o años desde la fecha de factura.
 *
 * La usan dos consumidores que NO deben duplicar este cálculo: el endpoint
 * de chequeo en vivo (ProductoController::garantia(), solo informativo) y
 * OrdenService::crear() (validación dura antes de guardar).
 */
class ProductoGarantiaService
{
    /** gbl.unidad_medida.codigo -> especificador de DateInterval. Ver seed en Version20260912120100/Version20260928120000. */
    private const UNIDAD_A_INTERVALO = [
        'Day' => 'D',
        'Month' => 'M',
        'Year' => 'Y',
    ];

    public function __construct(private readonly ProductoCalidadRepository $productoCalidad)
    {
    }

    /**
     * @return array{tieneGarantia: bool, vigente: ?bool, fechaLimite: ?\DateTimeImmutable, valor: ?int, unidadMedida: ?string}
     */
    public function validar(Producto $producto, ?\DateTimeImmutable $fechaFactura): array
    {
        $sinGarantia = ['tieneGarantia' => false, 'vigente' => null, 'fechaLimite' => null, 'valor' => null, 'unidadMedida' => null];

        $fila = $this->productoCalidad->buscarGarantia($producto);
        if ($fila === null) {
            return $sinGarantia;
        }

        $letraIntervalo = $fila['unidad_codigo'] !== null ? (self::UNIDAD_A_INTERVALO[$fila['unidad_codigo']] ?? null) : null;
        $valor = (int) round((float) $fila['valor']);

        // Config incompleta (sin unidad de medida reconocida, o valor <= 0) = tratada igual que "sin garantía": no hay
        // suficiente información para hacer cumplir la regla de negocio.
        if ($letraIntervalo === null || $valor <= 0) {
            return $sinGarantia;
        }

        if ($fechaFactura === null) {
            return ['tieneGarantia' => true, 'vigente' => null, 'fechaLimite' => null, 'valor' => $valor, 'unidadMedida' => $fila['unidad_descripcion']];
        }

        $fechaLimite = $fechaFactura->add(new \DateInterval("P{$valor}{$letraIntervalo}"));
        $vigente = new \DateTimeImmutable('today') <= $fechaLimite;

        return ['tieneGarantia' => true, 'vigente' => $vigente, 'fechaLimite' => $fechaLimite, 'valor' => $valor, 'unidadMedida' => $fila['unidad_descripcion']];
    }
}
