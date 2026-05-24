<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Request;

use KlarnaPayment\Components\Client\Struct\Address;
use KlarnaPayment\Components\Client\Struct\Attachment;
use KlarnaPayment\Components\Client\Struct\Customer;
use KlarnaPayment\Components\Client\Struct\LineItem;
use KlarnaPayment\Components\Client\Struct\Options;
use Shopware\Core\Framework\Struct\Struct;

class CreateOrderRequest extends Struct implements RequestInterface
{
    /** @var string */
    protected $method = 'POST';

    /** @var string */
    protected $endpoint = '/payments/v1/authorizations/{authorizationToken}/order';

    /** @var ?string */
    protected $salesChannel;

    /** @var ?string */
    protected $acquiringChannel;

    /** @var null|Attachment */
    protected $attachment;

    /** @var string */
    protected $orderNumber;

    /** @var string */
    protected $authorizationToken = '';

    /** @var bool */
    protected $autoCapture = false;

    /** @var string */
    protected $purchaseCountry;

    /** @var string */
    protected $purchaseCurrency;

    /** @var string */
    protected $locale;

    /** @var Options */
    protected $options;

    /** @var float */
    protected $orderAmount = 0.0;

    /** @var float */
    protected $orderTaxAmount = 0.0;

    /** @var LineItem[] */
    protected $orderLines = [];

    /** @var null|Address */
    protected $billingAddress;

    /** @var null|Address */
    protected $shippingAddress;

    /** @var string[] */
    protected $merchantUrls = [];

    /** @var null|string */
    protected $merchantData;

    /** @var null|Customer */
    protected $customer;

    public function getAcquiringChannel(): ?string
    {
        return $this->acquiringChannel;
    }

    public function getAttachment(): ?Attachment
    {
        return $this->attachment;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function getAuthorizationToken(): string
    {
        return $this->authorizationToken;
    }

    public function getAutoCapture(): bool
    {
        return $this->autoCapture;
    }

    public function getPurchaseCountry(): string
    {
        return $this->purchaseCountry;
    }

    public function getPurchaseCurrency(): string
    {
        return $this->purchaseCurrency;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getOptions(): Options
    {
        return $this->options;
    }

    public function getOrderAmount(): float
    {
        return $this->orderAmount;
    }

    public function getOrderTaxAmount(): float
    {
        return $this->orderTaxAmount;
    }

    /**
     * @return LineItem[]
     */
    public function getOrderLines(): array
    {
        return $this->orderLines;
    }

    public function getBillingAddress(): ?Address
    {
        return $this->billingAddress;
    }

    public function getShippingAddress(): ?Address
    {
        return $this->shippingAddress;
    }

    /**
     * @return string[]
     */
    public function getMerchantUrls(): array
    {
        return $this->merchantUrls;
    }

    public function getMerchantData(): ?string
    {
        if ($this->merchantData === null) {
            return null;
        }

        return mb_substr($this->merchantData, 0, 1024);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getEndpoint(): string
    {
        return str_replace('{authorizationToken}', $this->getAuthorizationToken(), $this->endpoint);
    }

    public function getSalesChannel(): ?string
    {
        return $this->salesChannel;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function jsonSerialize(): array
    {
        return [
            'merchant_reference1' => $this->getOrderNumber(),
            'acquiring_channel'   => $this->getAcquiringChannel(),
            'attachment'          => $this->getAttachment(),
            'auto_capture'        => $this->getAutoCapture(),
            'purchase_country'    => $this->getPurchaseCountry(),
            'purchase_currency'   => $this->getPurchaseCurrency(),
            'locale'              => $this->getLocale(),
            'options'             => $this->getOptions(),
            'order_amount'        => (int) round($this->getOrderAmount() * 100, 0),
            'order_tax_amount'    => (int) round($this->getOrderTaxAmount() * 100, 0),
            'order_lines'         => $this->getOrderLines(),
            'billing_address'     => $this->getBillingAddress(),
            'shipping_address'    => $this->getShippingAddress(),
            'merchant_urls'       => $this->getMerchantUrls(),
            'merchant_data'       => $this->getMerchantData(),
            'customer'            => $this->getCustomer(),
        ];
    }
}
