<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\EndpointHelper;

use KlarnaPayment\Components\Client\Request\RequestInterface;

interface EndpointHelperInterface
{
    public function resolveEndpointRegion(RequestInterface $request): string;
}
