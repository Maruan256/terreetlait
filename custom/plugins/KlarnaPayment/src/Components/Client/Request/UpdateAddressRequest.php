<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Request;

use KlarnaPayment\Components\Client\Struct\Address;

class UpdateAddressRequest extends AbstractPaymentRequest
{
    /** @var string */
    protected $method = 'PATCH';

    /** @var string */
    protected $endpoint = '/ordermanagement/v1/orders/{order_id}/customer-details';

    /** @var ?Address */
    protected $billingAddress;

    /** @var Address */
    protected $shippingAddress;

    public function getBillingAddress(): ?Address
    {
        return $this->billingAddress;
    }

    public function getShippingAddress(): Address
    {
        return $this->shippingAddress;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getEndpoint(): string
    {
        return str_replace('{order_id}', $this->getKlarnaOrderId(), $this->endpoint);
    }

    public function jsonSerialize(): array
    {
        return [
            'billing_address'  => $this->getBillingAddress(),
            'shipping_address' => $this->getShippingAddress(),
        ];
    }
}
