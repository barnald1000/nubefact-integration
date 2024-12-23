<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFact;

enum IGVType: int
{
    /**
     * Gravado - Operación Onerosa
     */
    case GravadoOnerous = 1;

    /**
     * Gravado – Premio o retiro
     */
    case GravadoPrize = 2;

    /**
     * Gravado – Donación
     */
    case GravadoDonation = 3;

    /**
     * Gravado – Retiro
     */
    case GravadoWithdrawal = 4;

    /**
     * Gravado – Publicidad
     */
    case GravadoAdvertising = 5;

    /**
     * Gravado – Bonificaciones
     */
    case GravadoBonuses = 6;

    /**
     * Gravado – Regalos a empleados
     */
    case GravadoEmployeeGifts = 7;

    /**
     * Exonerado - Operación Onerosa
     */
    case ExoneratedOnerous = 8;

    /**
     * Inafecto - Operación Onerosa
     */
    case ExemptOnerous = 9;

    /**
     * Inafecto – Bonificación o retiro
     */
    case ExemptBonus = 10;

    /**
     * Inafecto – Retiro
     */
    case ExemptWithdrawal = 11;

    /**
     * Inafecto – Muestras médicas
     */
    case ExemptMedicalSamples = 12;

    /**
     * Inafecto - Convenio colectivo
     */
    case ExemptCollectiveAgreement = 13;

    /**
     * Inafecto – Premio o retiro
     */
    case ExemptPrize = 14;

    /**
     * Inafecto - Publicidad
     */
    case ExemptAdvertising = 15;

    /**
     * Exportación
     */
    case Exportation = 16;

    /**
     * Exonerado - Transferencia gratuita
     */
    case ExoneratedFreeTransfer = 17;
}
