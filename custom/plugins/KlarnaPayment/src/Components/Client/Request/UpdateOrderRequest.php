<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Request;

use KlarnaPayment\Components\Client\Struct\LineItem;
use Shopware\Core\Framework\Struct\Struct;

class UpdateOrderRequest extends Struct implements RequestInterface
{
    /** @var string */
    protected $method = 'PATCH';

    /** @var string */
    protected $endpoint = '/ordermanagement/v1/orders/{order_id}/authorization';

    /** @var null|string */
    protected $salesChannel;

    /** @var string */
    protected $orderId = '';

    /** @var string */
    protected $klarnaOrderId = '';

    /** @var LineItem[] */
    protected $lineItems = [];

    /** @var float */
    protected $orderAmount = 0.0;

    /**
     * @return LineItem[]
     */
    public function getLineItems(): array
    {
        return $this->lineItems;
    }

    public function getOrderAmount(): float
    {
        return $this->orderAmount;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getEndpoint(): string
    {
        return str_replace('{order_id}', $this->klarnaOrderId, $this->endpoint);
    }

    public function getSalesChannel(): ?string
    {
        return $this->salesChannel;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getKlarnaOrderId(): string
    {
        return $this->klarnaOrderId;
    }

    public function jsonSerialize(): array
    {
        return [
            'order_amount' => (int) round($this->getOrderAmount() * 100, 0),
            'order_lines'  => $this->getLineItems(),
        ];
    }
}
