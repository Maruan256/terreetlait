<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Event;

use KlarnaPayment\Components\Struct\ShippingInfo;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\NestedEvent;

class AddCaptureShippingInfo extends NestedEvent
{
    /** @var ShippingInfo */
    private $data;

    /** @var string */
    private $orderId;

    /** @var string */
    private $orderTransactionId;

    /** @var string */
    private $shippingMethodId;

    /** @var Context */
    private $context;

    public function __construct(
        ShippingInfo $data,
        string $orderId,
        string $orderTransactionId,
        string $shippingMethodId,
        Context $context
    ) {
        $this->data               = $data;
        $this->orderId            = $orderId;
        $this->orderTransactionId = $orderTransactionId;
        $this->shippingMethodId   = $shippingMethodId;
        $this->context            = $context;
    }

    public function getData(): ShippingInfo
    {
        return $this->data;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getOrderTransactionId(): string
    {
        return $this->orderTransactionId;
    }

    public function getShippingMethodId(): string
    {
        return $this->shippingMethodId;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
