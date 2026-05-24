<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Struct;

use Shopware\Core\Framework\Struct\Struct;

class LineItem extends Struct
{
    public const TYPE_PHYSICAL     = 'physical';
    public const TYPE_DISCOUNT     = 'discount';
    public const TYPE_SHIPPING_FEE = 'shipping_fee';
    public const TYPE_SALES_TAX    = 'sales_tax';
    public const TYPE_DIGITAL      = 'digital';
    public const TYPE_GIFT_CARD    = 'gift_card';
    public const TYPE_STORE_CREDIT = 'store_credit';
    public const TYPE_SURCHARGE    = 'surcharge';

    /** @var string */
    protected $type = self::TYPE_PHYSICAL;

    /** @var string */
    protected $productId = '';

    /** @var string */
    protected $reference = '';

    /** @var string */
    protected $name = '';

    /** @var int */
    protected $quantity = 0;

    /** @var int */
    protected $capturedQuantity = 0;

    /** @var int */
    protected $refundedQuantity = 0;

    /** @var ?string */
    protected $quantityUnit;

    /** @var float */
    protected $unitPrice = 0.0;

    /** @var float */
    protected $taxRate = 0.0;

    /** @var float */
    protected $totalAmount = 0.0;

    /** @var float */
    protected $totalTaxAmount = 0.0;

    /** @var null|ProductIdentifier */
    protected $productIdentifier;

    /** @var null|string */
    protected $merchantData;

    /** @var null|string */
    protected $productUrl;

    /** @var null|string */
    protected $imageUrl;

    public function getType(): string
    {
        return $this->type;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getCapturedQuantity(): int
    {
        return $this->capturedQuantity;
    }

    public function getRefundedQuantity(): int
    {
        return $this->refundedQuantity;
    }

    public function getQuantityUnit(): ?string
    {
        return $this->quantityUnit;
    }

    public function getUnitPrice(): float
    {
        return $this->unitPrice;
    }

    public function getTaxRate(): float
    {
        return $this->taxRate;
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function getTotalTaxAmount(): float
    {
        return $this->totalTaxAmount;
    }

    public function getProductIdentifier(): ?ProductIdentifier
    {
        return $this->productIdentifier;
    }

    public function getMerchantData(): ?string
    {
        if ($this->merchantData === null) {
            return null;
        }

        return mb_substr($this->merchantData, 0, 255);
    }

    public function getProductUrl(): ?string
    {
        return $this->productUrl;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function jsonSerialize(): array
    {
        return  [
            'type'                => $this->getType(),
            'reference'           => $this->getReference(),
            'name'                => $this->getName(),
            'quantity'            => $this->getQuantity(),
            'captured_quantity'   => $this->getCapturedQuantity(),
            'refunded_quantity'   => $this->getRefundedQuantity(),
            'quantity_unit'       => $this->getQuantityUnit(),
            'unit_price'          => (int) round($this->getUnitPrice() * 100, 0),
            'tax_rate'            => (int) round($this->getTaxRate() * 100, 0),
            'total_amount'        => (int) round($this->getTotalAmount() * 100, 0),
            'total_tax_amount'    => (int) round($this->getTotalTaxAmount() * 100, 0),
            'product_identifiers' => $this->getProductIdentifier(),
            'merchant_data'       => $this->getMerchantData(),
            'product_url'         => $this->getProductUrl(),
            'image_url'           => $this->getImageUrl(),
        ];
    }
}
