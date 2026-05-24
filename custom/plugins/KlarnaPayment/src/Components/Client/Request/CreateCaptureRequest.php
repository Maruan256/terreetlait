<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Request;

use KlarnaPayment\Components\Client\Struct\LineItem;

class CreateCaptureRequest extends AbstractPaymentRequest
{
    /** @var string */
    protected $method = 'POST';

    /** @var string */
    protected $endpoint = '/ordermanagement/v1/orders/{order_id}/captures';

    /** @var float */
    protected $captureAmount = 0.0;

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

    public function getCaptureAmount(): float
    {
        return $this->captureAmount;
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
            'captured_amount' => (int) round($this->getCaptureAmount() * 100, 0),
            'description'     => $this->getDescription(),
            'reference'       => $this->getReference(),
            'order_lines'     => $this->getOrderLines(),
        ];
    }
}
