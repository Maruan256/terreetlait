<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\ReleaseRemainingAuthorization;

use KlarnaPayment\Components\Client\Request\ReleaseRemainingAuthorizationRequest;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

interface ReleaseRemainingAuthorizationHydratorInterface
{
    public function hydrate(RequestDataBag $dataBag): ReleaseRemainingAuthorizationRequest;
}
