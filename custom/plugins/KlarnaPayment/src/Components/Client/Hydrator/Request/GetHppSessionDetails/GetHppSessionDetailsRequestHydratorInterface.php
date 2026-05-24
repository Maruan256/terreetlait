<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\GetHppSessionDetails;

use KlarnaPayment\Components\Client\Request\GetHppSessionDetailsRequest;

use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface GetHppSessionDetailsRequestHydratorInterface
{
    public function hydrate(string $sessionId, SalesChannelContext $salesChannelContext, string $countryIso = ''): GetHppSessionDetailsRequest;
}
