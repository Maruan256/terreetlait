<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Request;

class ReleaseRemainingAuthorizationRequest extends AbstractPaymentRequest
{
    /** @var string */
    protected $method = 'GET';

    /** @var string */
    protected $endpoint = '/ordermanagement/v1/orders/{order_id}/release-remaining-authorization';

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
            'klarna_order_id' => $this->getKlarnaOrderId(),
            'sales_channel'   => $this->getSalesChannel(),
        ];
    }
}
