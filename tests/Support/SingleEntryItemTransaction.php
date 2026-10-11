<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests\Support;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Savia\NubeFactIntegration\Contracts\Customer;
use Savia\NubeFactIntegration\Contracts\FinancialTransaction;
use Savia\NubeFactIntegration\Keywords\Currency;
use Savia\NubeFactIntegration\Keywords\DocumentType;
use Savia\NubeFactIntegration\Keywords\IdentificationType;
use Savia\NubeFactIntegration\Keywords\IgvRate;
use Savia\NubeFactIntegration\Lines\Amount;
use Savia\NubeFactIntegration\Lines\IGVTreatment;
use Savia\NubeFactIntegration\Lines\Item;
use Savia\NubeFactIntegration\Lines\Line;
use Savia\NubeFactIntegration\Lines\Lines;
use Savia\NubeFactIntegration\Lines\Quantity;
use Savia\NubeFactIntegration\Lines\Unit;

use function sprintf;

final readonly class SingleEntryItemTransaction implements FinancialTransaction
{
    public function __construct(
        private array $billingInfo,
        private int $serialNumber,
        private string $serialCode,
    ) {}

    public function id(): string
    {
        return (string) $this->billingInfo['sac_solicitud_id'];
    }

    public function number(): int
    {
        return $this->serialNumber;
    }

    public function serial(): string
    {
        return $this->serialCode;
    }

    public function type(): DocumentType
    {
        return $this->isInvoiceRequired() ? DocumentType::Invoice : DocumentType::Receipt;
    }

    public function igvRate(): IgvRate
    {
        return IgvRate::standard();
    }

    public function customer(): Customer
    {
        $isInvoiceRequired = $this->isInvoiceRequired();

        $address = empty($this->billingInfo['sac_direccion_facturacion'])
            ? $this->billingInfo['alumno_direccion']
            : $this->billingInfo['sac_direccion_facturacion'];

        $email = empty($this->billingInfo['sac_mail_facturacion'])
            ? $this->billingInfo['alumno_correo']
            : $this->billingInfo['sac_mail_facturacion'];

        return new GenericClient(
            denomination        : $isInvoiceRequired ? $this->billingInfo['sac_razon_social'] : $this->clientDemonstration(),
            email               : $email,
            address             : $address,
            identificationNumber: $isInvoiceRequired ? (string) $this->billingInfo['sac_ruc'] : $this->clientDocumentNumber(),
            identificationType  : $isInvoiceRequired ? IdentificationType::Ruc : IdentificationType::Dni,
        );
    }

    public function issueDate(): DateTimeInterface
    {
        $limaTimeZone = new DateTimeZone('America/Lima');

        return new DateTimeImmutable('now', $limaTimeZone);
    }

    public function currency(): Currency
    {
        return Currency::PEN;
    }

    public function lines(): Lines
    {
        return new Lines([
            Line::onerous(
                item     : Item::of($this->code(), $this->description(), Unit::service()),
                quantity : Quantity::of($this->quantity()),
                unitPrice: Amount::of(sprintf('%.2f', $this->unitaryPrice())),
                tax      : IGVTreatment::taxedIncluded(),
            ),
        ]);
    }

    private function code(): string
    {
        return $this->id();
    }

    private function description(): string
    {
        return sprintf('Ciclo: %s Modalidad: %s', $this->billingInfo['ciclo_nombre'], $this->billingInfo['sac_modalidad']);
    }

    private function quantity(): int
    {
        return 1;
    }

    private function unitaryPrice(): float
    {
        return $this->billingInfo['unitary_price'];
    }

    private function isInvoiceRequired(): bool
    {
        return $this->billingInfo['sac_comprobante'] == 'Factura' || $this->billingInfo['sac_tipo_comprobante'] == 'FACTURA';
    }

    private function clientDemonstration(): string
    {
        if ($this->clientHasAGuardian()) {
            $names = [
                $this->billingInfo['alumno_apellido_apoderado'] ?? '',
                $this->billingInfo['alumno_apellido_m_apoderado'] ?? '',
                $this->billingInfo['alumno_nombre_apoderado'] ?? '',
            ];
        } else {
            $names = [
                $this->billingInfo['alumno_apellido'] ?? '',
                $this->billingInfo['alumno_apellido_materno'] ?? '',
                $this->billingInfo['alumno_nombre'] ?? '',
            ];
        }

        return implode(' ', array_filter($names));
    }

    private function clientDocumentNumber(): ?string
    {
        $paymentDNI = $this->billingInfo['sac_dni_pago'];

        if (!empty($paymentDNI) && $paymentDNI != 0) {
            return $paymentDNI;
        }

        return $this->clientHasAGuardian()
            ? $this->billingInfo['alumno_dni_apoderado']
            : $this->billingInfo['alumno_dni'];
    }

    private function clientHasAGuardian(): bool
    {
        return $this->billingInfo['sac_destinatario_boleta'] == 'Apoderado';
    }
}
