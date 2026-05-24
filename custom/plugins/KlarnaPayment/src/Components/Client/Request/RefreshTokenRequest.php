<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Request;

class RefreshTokenRequest extends AbstractPaymentRequest
{
    /** @var string */
    protected $method = 'POST';

    /** @var string */
    protected $refreshToken = '';

    /** @var string */
    protected $clientId = '';

    /** @var string */
    protected $grantType = 'refresh_token';

    /** @var string */
    protected $endpoint = '/eu/lp/idp/oauth2/token';

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getGrantType(): string
    {
        return $this->grantType;
    }

    public function jsonSerialize(): array
    {
        return [
            'refresh_token' => $this->getRefreshToken(),
            'client_id' => $this->getClientId(),
            'grant_type' => $this->getGrantType()
        ];
    }
}
