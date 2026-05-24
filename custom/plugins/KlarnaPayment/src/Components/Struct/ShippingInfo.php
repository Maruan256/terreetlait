<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Struct;

use Shopware\Core\Framework\Struct\Struct;

class ShippingInfo extends Struct
{
    /** @var string */
    protected $trackingNumber;

    /** @var null|string */
    protected $trackingUri;

    /** @var null|string */
    protected $shippingCompany;

    /** @var null|string */
    protected $shippingMethod;

    /** @var null|string */
    protected $returnShippingCompany;

    /** @var null|string */
    protected $returnShippingMethod;

    /** @var null|string */
    protected $returnTrackingNumber;

    /** @var null|string */
    protected $returnTrackingUri;

    public function getTrackingNumber(): string
    {
        return $this->trackingNumber;
    }

    public function getTrackingUri(): ?string
    {
        if ($this->trackingUri) {
            return str_replace('%s', $this->getTrackingNumber(), $this->trackingUri);
        }

        return null;
    }

    public function getShippingCompany(): ?string
    {
        return $this->shippingCompany;
    }

    public function getShippingMethod(): ?string
    {
        return $this->shippingMethod;
    }

    public function getReturnShippingCompany(): ?string
    {
        return $this->returnShippingCompany;
    }

    public function getReturnShippingMethod(): ?string
    {
        return $this->returnShippingMethod;
    }

    public function getReturnTrackingNumber(): ?string
    {
        return $this->returnTrackingNumber;
    }

    public function getReturnTrackingUri(): ?string
    {
        return $this->returnTrackingUri;
    }

    public function jsonSerialize(): array
    {
        return [
            'shipping_company'        => $this->getShippingCompany(),
            'shipping_method'         => $this->getShippingMethod(),
            'tracking_number'         => $this->getTrackingNumber(),
            'tracking_uri'            => $this->getTrackingUri(),
            'return_shipping_company' => $this->getReturnShippingCompany(),
            'return_tracking_number'  => $this->getReturnTrackingNumber(),
            'return_tracking_uri'     => $this->getReturnTrackingUri(),
        ];
    }
}
