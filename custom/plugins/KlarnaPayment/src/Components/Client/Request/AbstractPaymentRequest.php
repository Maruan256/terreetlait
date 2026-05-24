<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Request;

use Shopware\Core\Framework\Struct\Struct;

abstract class AbstractPaymentRequest extends Struct implements RequestInterface
{
    /** @var ?string */
    protected $salesChannel;
    /** @var ?string */
    protected $orderId = '';
    /** @var string */
    protected $klarnaOrderId = '';

    public function getSalesChannel(): ?string
    {
        return $this->salesChannel;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function getKlarnaOrderId(): string
    {
        return $this->klarnaOrderId;
    }
}
