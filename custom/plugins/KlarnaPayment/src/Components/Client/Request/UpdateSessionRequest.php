<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Request;

use KlarnaPayment\Components\Client\Struct\Address;
use KlarnaPayment\Components\Client\Struct\Attachment;
use KlarnaPayment\Components\Client\Struct\Customer;
use KlarnaPayment\Components\Client\Struct\LineItem;
use KlarnaPayment\Components\Client\Struct\Options;
use Shopware\Core\Framework\Struct\Struct;

class UpdateSessionRequest extends Struct implements RequestInterface
{
    /** @var string */
    protected $method = 'POST';

    /** @var string */
    protected $endpoint = '/payments/v1/sessions/{session_id}';

    /** @var string */
    protected $sessionId = '';

    /** @var ?string */
    protected $salesChannel;

    /** @var ?string */
    protected $acquiringChannel;

    /** @var null|Attachment */
    protected $attachment;

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

    /** @var null|Customer */
    protected $customer;

    /** @var string[] */
    protected $merchantUrls = [];

    /** @var null|string */
    protected $merchantData;

    public function getAcquiringChannel(): ?string
    {
        return $this->acquiringChannel;
    }

    public function getAttachment(): ?Attachment
    {
        return $this->attachment;
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

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

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
        return str_replace('{session_id}', $this->sessionId, $this->endpoint);
    }

    public function getSalesChannel(): ?string
    {
        return $this->salesChannel;
    }

    public function jsonSerialize(): array
    {
        $data = [
            'acquiring_channel' => $this->getAcquiringChannel(),
            'attachment'        => $this->getAttachment(),
            'purchase_country'  => $this->getPurchaseCountry(),
            'purchase_currency' => $this->getPurchaseCurrency(),
            'locale'            => $this->getLocale(),
            'options'           => $this->getOptions(),
            'order_amount'      => (int) round($this->getOrderAmount() * 100, 0),
            'order_tax_amount'  => (int) round($this->getOrderTaxAmount() * 100, 0),
            'billing_address'   => $this->getBillingAddress(),
            'shipping_address'  => $this->getShippingAddress(),
            'order_lines'       => $this->getOrderLines(),
            'customer'          => $this->getCustomer(),
            'merchant_data'     => $this->getMerchantData(),
        ];

        if (!empty($this->getMerchantUrls())) {
            $data['merchant_urls'] = $this->getMerchantUrls();
        }

        return $data;
    }
}
