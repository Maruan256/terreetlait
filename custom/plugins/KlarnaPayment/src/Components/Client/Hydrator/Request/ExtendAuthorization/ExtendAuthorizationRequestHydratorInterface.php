<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\ExtendAuthorization;

use KlarnaPayment\Components\Client\Request\ExtendAuthorizationRequest;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

interface ExtendAuthorizationRequestHydratorInterface
{
    public function hydrate(RequestDataBag $dataBag): ExtendAuthorizationRequest;
}
