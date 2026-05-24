<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Request;

use Shopware\Core\Framework\Struct\Struct;

class UpdateMerchantReferencesRequest extends Struct implements RequestInterface
{
    /** @var string */
    protected $method = 'PATCH';

    /** @var string */
    protected $endpoint = '/ordermanagement/v1/orders/{klarna_order_id}/merchant-references';

    /** @var null|string */
    protected $salesChannel;

     /** @var string */
     protected $orderId = '';

    /** @var string */
    protected $klarnaOrderId = '';

    /** @var string */
    protected $merchantReference1 = '';

    /** @var string */
    protected $merchantReference2 = '';

    public function getMerchantReference1(): string
    {
        return $this->merchantReference1;
    }

    public function getMerchantReference2(): string
    {
        return $this->merchantReference2;
    }


    public function getMethod(): string
    {
        return $this->method;
    }

    public function getEndpoint(): string
    {
        return str_replace('{klarna_order_id}', $this->klarnaOrderId, $this->endpoint);
    }

    public function getKlarnaOrderId(): string
    {
        return $this->klarnaOrderId;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getSalesChannel(): ?string
    {
        return $this->salesChannel;
    }

    public function jsonSerialize(): array
    {
        return [
            'merchant_reference1' => $this->getMerchantReference1(),
            'merchant_reference2' => $this->getMerchantReference2()
        ];
    }
}
