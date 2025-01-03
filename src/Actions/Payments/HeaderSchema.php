<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Actions\Payments;

use Savia\NubeFactIntegration\Contracts\TransactionEntry;
use Savia\NubeFactIntegration\Schema;
use Savia\NubeFactIntegration\TransactionSummarizer;

final class HeaderSchema extends Schema
{
    private readonly TransactionSummarizer $summarizer;

    public function __construct(
        private readonly TransactionEntry $entry
    )
    {
        $this->summarizer = new TransactionSummarizer($entry);
    }

    protected function structure(): array
    {
        return [
            "tipo_de_comprobante"         => $this->entry->type()->value,
            "serie"                       => $this->entry->serial(),
            "numero"                      => $this->entry->number(),
            "sunat_transaction"           => 1,
            "cliente_tipo_de_documento"   => $this->entry->customer()->identificationType()->value,
            "cliente_numero_de_documento" => $this->entry->customer()->identificationNumber(),
            "cliente_denominacion"        => $this->entry->customer()->denomination(),
            "cliente_direccion"           => $this->entry->customer()->address(),
            "cliente_email"               => $this->entry->customer()->email(),
            "fecha_de_emision"            => $this->entry->issueDate()->format('Y-m-d'),
            "moneda"                      => $this->entry->currency()->value,
            "porcentaje_de_igv"           => $this->entry->taxPercentage(),
            "total_gravada"               => $this->summarizer->taxableTotal(),
            "total_igv"                   => $this->summarizer->totalTaxes(),
            "total"                       => $this->summarizer->total(),

            "enviar_automaticamente_a_la_sunat" => true,
            "enviar_automaticamente_al_cliente" => true,
        ];
    }

    protected static function required(): array
    {
        return [
            "tipo_de_comprobante",
            "serie",
            "numero",
            "sunat_transaction",
            "cliente_tipo_de_documento",
            "cliente_numero_de_documento",
            "cliente_denominacion",
            "cliente_direccion",
            "cliente_email",
            "fecha_de_emision",
            "moneda",
            "porcentaje_de_igv",
            "total_gravada",
            "total_igv",
            "total",
        ];
    }

    protected static function valuesToRound(): array
    {
        return [
            "total_gravada",
            "total_igv",
            "total",
        ];
    }
}