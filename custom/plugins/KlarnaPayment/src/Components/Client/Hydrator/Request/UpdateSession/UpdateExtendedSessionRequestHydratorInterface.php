<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\UpdateSession;

use KlarnaPayment\Components\Client\Request\UpdateSessionRequest;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface UpdateExtendedSessionRequestHydratorInterface
{
    public function hydrate(string $sessionId, Cart $cart, SalesChannelContext $salesChannelContext): UpdateSessionRequest;
}
