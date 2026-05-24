<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\GetSessionDetails;

use KlarnaPayment\Components\Client\Request\GetSessionDetailsRequest;

use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface GetSessionDetailsRequestHydratorInterface
{
    public function hydrate(string $sessionId, SalesChannelContext $salesChannelContext): GetSessionDetailsRequest;
}
