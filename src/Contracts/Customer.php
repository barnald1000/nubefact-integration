<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Contracts;

interface Customer extends Identifiable
{
    /**
     * Returns the customer's denomination (name or business name).
     *
     * This is typically used to refer to the customer, especially in contexts like invoices or orders.
     *
     * @return string The customer's denomination (name or business name).
     */
    public function denomination(): string;

    /**
     * Returns the customer's email address.
     *
     * This is an optional field, as not all customers may have an email address.
     *
     * @return string|null The customer's email address, or null if not provided.
     */
    public function email(): ?string;

    /**
     * Returns the customer's address.
     *
     * This is an optional field, as not all customers may have a specified address.
     *
     * @return string|null The customer's address, or null if not provided.
     */
    public function address(): ?string;
}