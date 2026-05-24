<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Request;

use KlarnaPayment\Components\Struct\ShippingInfo;

class AddShippingInfoRequest extends AbstractPaymentRequest
{
    /** @var string */
    protected $method = 'POST';

    /** @var string */
    protected $endpoint = '/ordermanagement/v1/orders/{order_id}/captures/{capture_id}/shipping-info';

    /** @var string */
    protected $captureId;

    /** @var ShippingInfo[] */
    protected $shippingInfos;

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getEndpoint(): string
    {
        return str_replace(['{order_id}', '{capture_id}'], [$this->getKlarnaOrderId(), $this->getCaptureId()], $this->endpoint);
    }

    public function getCaptureId(): string
    {
        return $this->captureId;
    }

    /**
     * @return ShippingInfo[]
     */
    public function getShippingInfos(): array
    {
        return $this->shippingInfos;
    }

    public function jsonSerialize(): array
    {
        return [
            'shipping_info' => $this->getShippingInfos(),
        ];
    }
}
