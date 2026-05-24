<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\CreateHppSession;

use KlarnaPayment\Components\Client\Request\CreateHppSessionRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\Checkout\Cart\Cart;

interface CreateHppSessionRequestHydratorInterface
{
    public function hydrate(array $klarnaSession, Request $request, SalesChannelContext $context): CreateHppSessionRequest;
}
