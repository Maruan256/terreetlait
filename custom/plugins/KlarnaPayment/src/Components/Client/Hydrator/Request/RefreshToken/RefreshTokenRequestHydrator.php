<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\RefreshToken;

use KlarnaPayment\Components\Client\Request\RefreshTokenRequest;

use Monolog\Logger;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class RefreshTokenRequestHydrator implements RefreshTokenRequestHydratorInterface
{
    /** @var Logger */
    private $logger;

    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }

    public function hydrate(RequestDataBag $dataBag): RefreshTokenRequest
    {
        $request = new RefreshTokenRequest();

        $request->assign([
            'refreshToken' => $dataBag->get('refresh_token'),
            'clientId' => $dataBag->get('client_id')
        ]);

        return $request;
    }
}
