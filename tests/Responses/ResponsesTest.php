<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests\Responses;

use PHPUnit\Framework\Attributes\Test;
use Savia\NubeFactIntegration\Responses\Error;
use Savia\NubeFactIntegration\Responses\Success;
use Savia\NubeFactIntegration\Tests\Support\LinesTransaction;
use Savia\NubeFactIntegration\Tests\TestCase;

final class ResponsesTest extends TestCase
{
    #[Test]
    public function a_success_reads_the_nubefact_response_fields(): void
    {
        $success = new Success(new LinesTransaction(), [
            'aceptada_por_sunat'    => false,
            'cadena_para_codigo_qr' => '20600695771 | 01 | FFF1 | 000001',
            'codigo_hash'           => 'xMLFMnbgp1/bHEy572RKRTE9hPY=',
            'codigo_de_barras'      => '20600695771|01|FFF1|1',
            'enlace_del_pdf'        => 'https://www.nubefact.com/cpe/abc.pdf',
            'enlace_del_xml'        => 'https://www.nubefact.com/cpe/abc.xml',
            'invoice'               => ['numero' => 1],
        ]);

        self::assertFalse($success->isAccept());
        self::assertSame('20600695771 | 01 | FFF1 | 000001', $success->qrCode());
        self::assertSame('xMLFMnbgp1/bHEy572RKRTE9hPY=', $success->hashCode());
        self::assertSame('20600695771|01|FFF1|1', $success->barCode());
        self::assertSame('https://www.nubefact.com/cpe/abc.pdf', $success->pdfDocument());
        self::assertSame('https://www.nubefact.com/cpe/abc.xml', $success->xmlDocument());
        self::assertSame(['numero' => 1], $success->content());
    }

    #[Test]
    public function an_error_reads_the_nubefact_error_fields(): void
    {
        $error = new Error(new LinesTransaction(), 'Client error: 400 Bad Request', ['errors' => ['Serie inválida'], 'codigo' => '21']);

        self::assertFalse($error->isAccept());
        self::assertSame(21, $error->code());
        self::assertSame(['Serie inválida'], $error->messages());
        self::assertSame('Client error: 400 Bad Request', $error->error());
        self::assertSame(['is_accept' => false, 'error' => 'Client error: 400 Bad Request', 'messages' => ['Serie inválida']], $error->toArray());
    }

    #[Test]
    public function an_error_without_a_body_has_no_code_nor_messages(): void
    {
        $error = new Error(new LinesTransaction(), 'Connection refused', null);

        self::assertSame(0, $error->code());
        self::assertNull($error->messages());
        self::assertSame('Connection refused', $error->error());
    }
}
