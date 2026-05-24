<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Request;

use KlarnaPayment\Components\Client\Struct\Options;
use Shopware\Core\Framework\Struct\Struct;

class CreateHppSessionRequest extends Struct implements RequestInterface
{
    public const PLACE_ORDER_MODE_PLACE_ORDER = 'PLACE_ORDER';
    public const PLACE_ORDER_MODE_CAPTURE_ORDER = 'CAPTURE_ORDER';
    public const PLACE_ORDER_MODE_NONE = 'NONE';

    public const PURCHASE_TYPE_BUY = 'BUY';
    public const PURCHASE_TYPE_RENT = 'RENT';
    public const PURCHASE_TYPE_BOOK = 'BOOK';
    public const PURCHASE_TYPE_SUBSCRIBE = 'SUBSCRIBE';
    public const PURCHASE_TYPE_DOWNLOAD = 'DOWNLOAD';
    public const PURCHASE_TYPE_ORDER = 'ORDER';
    public const PURCHASE_TYPE_CONTINUE = 'CONTINUE';

    /** @var string */
    protected $method = 'POST';

    /** @var string */
    protected $endpoint = '/hpp/v1/sessions';

    /** @var ?string */
    protected $salesChannel;

    /** @var string[] */
    protected $merchantUrls = [];

    /** @var Options */
    protected $options;

    /** @var string */
    protected $paymentSessionUrl = '/payments/v1/sessions/{session_id}';

    /** @var string */
    protected $sessionId;

    /** @var ?string */
    protected $profileId;

    /** @var string */
    protected $endpointBaseUrl;

    /** @var string */
    protected $purchaseCountry;

    public function getMerchantUrls(): array
    {
        return $this->merchantUrls;
    }

    public function getOptions(): Options
    {
        return $this->options;
    }

    public function getPurchaseCountry(): string
    {
        return $this->purchaseCountry;
    }

    public function getPaymentSessionUrl(): string
    {
        return $this->endpointBaseUrl . str_replace('{session_id}', $this->sessionId, $this->paymentSessionUrl);
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getProfileId(): ?string
    {
        return $this->profileId;
    }

    public function getEndpointBaseUrl(): string
    {
        return $this->endpointBaseUrl;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getSalesChannel(): ?string
    {
        return $this->salesChannel;
    }

    public function jsonSerialize(): array
    {
        $data = [
            'options'           => $this->getOptions(),
            'payment_session_url' => $this->getPaymentSessionUrl(),
            'sessionId' => $this->getSessionId(),
            'profile_id' => $this->getProfileId(),
            'endpoint_base_url' => $this->getEndpointBaseUrl(),
            'purchase_country'  => $this->getPurchaseCountry()
        ];

        if (!empty($this->getMerchantUrls())) {
            $data['merchant_urls'] = $this->getMerchantUrls();
        }

        return $data;
    }
}
