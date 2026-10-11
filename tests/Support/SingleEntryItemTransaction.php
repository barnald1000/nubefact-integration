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
use Savia\NubeFactIntegration\Contracts\TransactionEntry;
use Savia\NubeFactIntegration\Contracts\TransactionEntryItem;
use Savia\NubeFactIntegration\Customer;
use Savia\NubeFactIntegration\Items;
use Savia\NubeFactIntegration\Keywords\Currency;
use Savia\NubeFactIntegration\Keywords\DocumentType;
use Savia\NubeFactIntegration\Keywords\IdentificationType;

use function sprintf;

final readonly class SingleEntryItemTransaction implements TransactionEntry, TransactionEntryItem
{
    private const IGV_PERCENTAGE = 18.0;

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

    public function taxPercentage(): float
    {
        return self::IGV_PERCENTAGE;
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

        return Customer::of(
            identificationType  : $isInvoiceRequired ? IdentificationType::Ruc : IdentificationType::Dni,
            identificationNumber: $isInvoiceRequired ? (string) $this->billingInfo['sac_ruc'] : $this->clientDocumentNumber(),
            denomination        : $isInvoiceRequired ? $this->billingInfo['sac_razon_social'] : $this->clientDemonstration(),
            address             : $address,
            email               : $email,
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

    public function items(): Items
    {
        return new Items([$this]);
    }

    public function total(): float
    {
        return (float) $this->billingInfo['sac_monto'];
    }

    public function netTotal(): float
    {
        $igvRate = self::IGV_PERCENTAGE / 100;

        return $this->total() / (1 + $igvRate);
    }

    public function totalDiscounts(): float
    {
        return 0;
    }

    public function totalTaxes(): float
    {
        return $this->total() - $this->netTotal();
    }

    public function code(): ?string
    {
        return $this->id();
    }

    public function description(): string
    {
        return sprintf('Ciclo: %s Modalidad: %s', $this->billingInfo['ciclo_nombre'], $this->billingInfo['sac_modalidad']);
    }

    public function quantity(): int
    {
        return 1;
    }

    public function measureCode(): string
    {
        return 'ZZ';
    }

    public function unitaryPrice(): float
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
                $thid->billingInfo['alumno_apellido_apoderado'] ?? '',
                $thid->billingInfo['alumno_apellido_m_apoderado'] ?? '',
                $thid->billingInfo['alumno_nombre_apoderado'] ?? '',
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
