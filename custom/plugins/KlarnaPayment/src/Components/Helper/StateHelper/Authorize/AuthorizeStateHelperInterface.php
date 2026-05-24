<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\StateHelper\Authorize;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

interface AuthorizeStateHelperInterface
{
    public function processOrderAuthorize(OrderEntity $order, Context $context): void;
}
