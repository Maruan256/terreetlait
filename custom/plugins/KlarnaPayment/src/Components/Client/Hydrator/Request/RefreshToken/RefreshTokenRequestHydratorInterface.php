<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\RefreshToken;

use KlarnaPayment\Components\Client\Request\RefreshTokenRequest;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

interface RefreshTokenRequestHydratorInterface
{
    public function hydrate(RequestDataBag $dataBag): RefreshTokenRequest;
}
