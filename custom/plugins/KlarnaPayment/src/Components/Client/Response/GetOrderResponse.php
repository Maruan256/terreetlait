<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Response;

use KlarnaPayment\Components\Client\Struct\Address;
use KlarnaPayment\Components\Client\Struct\LineItem;

class GetOrderResponse extends GenericResponse
{
    /** @var string */
    protected $orderId;

    /** @var string */
    protected $orderNumber;

    /** @var string */
    protected $orderStatus;

    /** @var string */
    protected $fraudStatus;

    /** @var string */
    protected $currency;

    /** @var float */
    protected $orderAmount;

    /** @var \DateTimeInterface */
    protected $expiryDate;

    /** @var string */
    protected $reference;

    /** @var float */
    protected $capturedAmount;

    /** @var float */
    protected $remainingAmount;

    /** @var float */
    protected $refundedAmount;

    /** @var LineItem[] */
    protected $orderLines = [];

    /** @var string */
    protected $initialPaymentMethod;

    /** @var string */
    protected $lastCaptureId;

    /** @var Address */
    protected $billingAddress;

    /** @var Address */
    protected $shippingAddress;

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function getOrderStatus(): string
    {
        return $this->orderStatus;
    }

    public function getFraudStatus(): string
    {
        return $this->fraudStatus;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getOrderAmount(): float
    {
        return $this->orderAmount;
    }

    public function getExpiryDate(): \DateTimeInterface
    {
        return $this->expiryDate;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getCapturedAmount(): float
    {
        return $this->capturedAmount;
    }

    public function getRemainingAmount(): float
    {
        return $this->remainingAmount;
    }

    public function getRefundedAmount(): float
    {
        return $this->refundedAmount;
    }

    /**
     * @return LineItem[]
     */
    public function getOrderLines(): array
    {
        return $this->orderLines;
    }

    public function getInitialPaymentMethod(): string
    {
        return $this->initialPaymentMethod;
    }

    public function getLastCaptureId(): string
    {
        return $this->lastCaptureId;
    }

    public function getBillingAddress(): Address
    {
        return $this->billingAddress;
    }

    public function getShippingAddress(): Address
    {
        return $this->shippingAddress;
    }

    public function jsonSerialize(): array
    {
        return [
            'order_id'               => $this->getOrderId(),
            'order_number'           => $this->getOrderNumber(),
            'order_status'           => $this->getOrderStatus(),
            'fraud_status'           => $this->getFraudStatus(),
            'currency'               => $this->getCurrency(),
            'order_amount'           => $this->getOrderAmount(),
            'expiry_date'            => $this->getExpiryDate()->format(\DateTime::ATOM),
            'reference'              => $this->getReference(),
            'captured_amount'        => $this->getCapturedAmount(),
            'remaining_amount'       => $this->getRemainingAmount(),
            'refunded_amount'        => $this->getRefundedAmount(),
            'order_lines'            => $this->getOrderLines(),
            'initial_payment_method' => $this->getInitialPaymentMethod(),
        ];
    }
}
