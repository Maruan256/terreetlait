<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Request;

class GetOrderRequest extends AbstractPaymentRequest
{
    /** @var string */
    protected $method = 'GET';

    /** @var string */
    protected $endpoint = '/ordermanagement/v1/orders/{order_id}';

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
        return [];
    }
}
