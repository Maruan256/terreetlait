<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Request;

class GetSessionDetailsRequest extends AbstractPaymentRequest
{
    /** @var string */
    protected $method = 'GET';

    /** @var string */
    protected $endpoint = '/payments/v1/sessions/{session_id}';

    /** @var string */
    protected $sessionId;

    /** @var string */
    protected $purchaseCountry;

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getEndpoint(): string
    {
        return str_replace('{session_id}', $this->getSessionId(), $this->endpoint);
    }

    public function getSessionId(): string 
    {
        return $this->sessionId;
    }

    public function getPurchaseCountry(): string
    {
        return $this->purchaseCountry;
    }

    public function jsonSerialize(): array
    {
        return [
            'purchaseCountry' => $this->getPurchaseCountry()
        ];
    }
}
