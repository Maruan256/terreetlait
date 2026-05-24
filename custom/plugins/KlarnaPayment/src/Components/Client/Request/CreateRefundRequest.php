<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Request;

use KlarnaPayment\Components\Client\Struct\LineItem;

class CreateRefundRequest extends AbstractPaymentRequest
{
    /** @var string */
    protected $method = 'POST';

    /** @var string */
    protected $endpoint = '/ordermanagement/v1/orders/{order_id}/refunds';

    /** @var float */
    protected $refundAmount = 0.0;

    /** @var ?string */
    protected $description;

    /** @var string */
    protected $reference = '';

    /** @var LineItem[] */
    protected $orderLines = [];

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getEndpoint(): string
    {
        return str_replace('{order_id}', $this->getKlarnaOrderId(), $this->endpoint);
    }

    public function getRefundAmount(): float
    {
        return $this->refundAmount;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    /**
     * @return LineItem[]
     */
    public function getOrderLines(): array
    {
        return $this->orderLines;
    }

    public function jsonSerialize(): array
    {
        return [
            'refunded_amount' => (int) round($this->getRefundAmount() * 100, 0),
            'description'     => $this->getDescription(),
            'reference'       => $this->getReference(),
            'order_lines'     => $this->getOrderLines(),
        ];
    }
}
