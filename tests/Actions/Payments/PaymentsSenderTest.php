<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests\Actions\Payments;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Savia\NubeFactIntegration\Actions\Payments\PaymentsSender;
use Savia\NubeFactIntegration\Keywords\Action;
use Savia\NubeFactIntegration\NubeFactService;
use Savia\NubeFactIntegration\Tests\Support\CountingTransaction;
use Savia\NubeFactIntegration\Tests\Support\SingleEntryItemTransaction;
use Savia\NubeFactIntegration\Tests\TestCase;

use function array_fill_keys;

final class PaymentsSenderTest extends TestCase
{
    #[Test]
    public function it_builds_the_items_from_the_transaction_lines(): void
    {
        $answer  = new Response(200);
        $service = new class ($answer) implements NubeFactService {
            public array $sent = [];

            public ?Action $action = null;

            public function __construct(private readonly ResponseInterface $answer) {}

            public function call(Action $action, array $data): ResponseInterface
            {
                $this->action    = $action;
                $this->sent      = $data;

                return $this->answer;
            }
        };

        $response = (new PaymentsSender($service))->send(new SingleEntryItemTransaction(self::invoiceBillingInfo(), 1, 'F001'));

        self::assertSame($answer, $response);
        self::assertSame(Action::GenerateDocument, $service->action);
        self::assertSame('F001', $service->sent['serie']);

        self::assertCount(1, $service->sent['items']);
        self::assertSame(100.0, $service->sent['items'][0]['valor_unitario']);
        self::assertSame(118.0, $service->sent['items'][0]['precio_unitario']);
        self::assertSame(100.0, $service->sent['items'][0]['subtotal']);
        self::assertSame(18.0, $service->sent['items'][0]['igv']);
        self::assertSame(118.0, $service->sent['items'][0]['total']);

        self::assertSame(100.0, $service->sent['total_gravada']);
        self::assertSame(18.0, $service->sent['total_igv']);
        self::assertSame(118.0, $service->sent['total']);
    }

    #[Test]
    public function it_asks_the_transaction_for_its_operation_once_per_send(): void
    {
        $service = new class () implements NubeFactService {
            public function call(Action $action, array $data): ResponseInterface
            {
                return new Response(200);
            }
        };

        $transaction = new CountingTransaction(new SingleEntryItemTransaction(self::invoiceBillingInfo(), 1, 'F001'));

        (new PaymentsSender($service))->send($transaction);

        self::assertSame(1, $transaction->operationCalls);
    }

    private static function invoiceBillingInfo(): array
    {
        return array_fill_keys([
            'alumno_apellido', 'alumno_apellido_apoderado', 'alumno_apellido_m_apoderado', 'alumno_apellido_materno',
            'alumno_dni', 'alumno_dni_apoderado', 'alumno_nombre', 'alumno_nombre_apoderado', 'sac_destinatario_boleta',
            'sac_dni_pago', 'alumno_direccion', 'alumno_correo',
        ], '') + [
            'sac_comprobante'           => 'Factura',
            'sac_tipo_comprobante'      => 'FACTURA',
            'sac_solicitud_id'          => 99,
            'sac_ruc'                   => '20600695771',
            'sac_razon_social'          => 'NUBEFACT SA',
            'sac_direccion_facturacion' => 'CALLE LIBERTAD 116',
            'sac_mail_facturacion'      => 'cliente@example.com',
            'ciclo_nombre'              => 'Ciclo 2026',
            'sac_modalidad'             => 'Presencial',
            'sac_monto'                 => 118,
            'unitary_price'             => 118,
        ];
    }
}
