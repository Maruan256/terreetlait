<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\CreateSession;

use KlarnaPayment\Components\Client\Request\CreateSessionRequest;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface CreateSessionRequestHydratorInterface
{
    public function hydrate(Cart $cart, SalesChannelContext $context): CreateSessionRequest;
}
