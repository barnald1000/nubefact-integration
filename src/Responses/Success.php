<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFact\Responses;

use Savia\NubeFact\Contracts\FinancialTransaction;

final readonly class Success extends Response
{
    public function __construct(FinancialTransaction $transaction, array $response)
    {
        parent::__construct($transaction, $response);
    }

    public function key(): string
    {
        return $this->get('key');
    }

    public function qrCode(): string
    {
        return $this->get('cadena_para_codigo_qr');
    }

    public function hashCode(): string
    {
        return $this->get('codigo_hash');
    }

    public function barCode()
    {
        return $this->get('codigo_de_barras');
    }

    public function pdfDocument(): string
    {
        return $this->get('enlace_del_pdf');
    }

    public function xmlDocument(): string
    {
        return $this->get('enlace_del_xml');
    }

    public function content(): string
    {
        return $this->get('invoice');
    }

    public function isAccept(): bool
    {
        return $this->get('aceptada_por_sunat');
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'key'          => $this->key(),
            'qr_code'      => $this->qrCode(),
            'hash_code'    => $this->hashCode(),
            'bar_code'     => $this->barCode(),
            'pdf_document' => $this->pdfDocument(),
            'xml_document' => $this->xmlDocument(),
            'document'     => $this->content(),
        ]);
    }
}