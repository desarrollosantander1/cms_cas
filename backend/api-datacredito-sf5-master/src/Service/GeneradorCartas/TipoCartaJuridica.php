<?php

namespace App\Service\GeneradorCartas;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Los 3 tipos de carta jurídica de v1. El "value" del enum es exactamente
 * el string que manda el frontend (y que el legacy usaba en su switch) —
 * se conserva la grafía "prejurico" sin "d" del legacy para no romper el
 * contrato.
 *
 * Cada tipo define: qué plantilla Twig renderiza, si la tabla lleva columna
 * de interés, si la sumatoria total incluye el interés, y el asunto de
 * correo (el prefijo institucional se lee de datacredito.configuracion,
 * CARTAS_ASUNTO_BASE — un enum no puede recibir servicios por constructor,
 * así que asuntoCorreo() lo recibe como argumento; los 2 llamadores
 * (CartaMailerService, CartaPdfService) lo resuelven vía ConfiguracionService).
 */
enum TipoCartaJuridica: string
{
    case DATA_CREDITO = 'data-credito';
    case PREJURIDICO_CON_INTERES = 'prejurico-con-interes';
    case PREJURIDICO_SIN_INTERES = 'prejurico-sin-interes';

    public function plantilla(): string
    {
        return match ($this) {
            self::DATA_CREDITO => 'generador_cartas/pdf/data_credito.html.twig',
            self::PREJURIDICO_CON_INTERES, self::PREJURIDICO_SIN_INTERES => 'generador_cartas/pdf/prejuridico.html.twig',
        };
    }

    /** ¿La tabla de facturas muestra la columna INTERES? (estadoCuentaInteres del legacy) */
    public function muestraInteres(): bool
    {
        return $this !== self::PREJURIDICO_SIN_INTERES;
    }

    /** ¿El TOTAL A PAGAR y la sumatoria incluyen el interés de mora? */
    public function totalConInteres(): bool
    {
        return $this !== self::PREJURIDICO_SIN_INTERES;
    }

    /** ¿Se puede sumar paramsDays a los días de mora antes de calcular el interés? (solo prejurídico con interés en el legacy) */
    public function admiteParamsDays(): bool
    {
        return $this === self::PREJURIDICO_CON_INTERES;
    }

    public function asuntoCorreo(string $asuntoBase): string
    {
        return $asuntoBase.'Carta de aviso acción jurídica';
    }

    public function nombreArchivo(): string
    {
        return $this->value.'.pdf';
    }

    public static function desde(string $valor): self
    {
        return self::tryFrom($valor)
            ?? throw new BadRequestHttpException(sprintf('Tipo de carta no válido: "%s".', $valor));
    }
}
