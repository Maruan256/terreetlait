<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\StateHelper\Capture;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

interface CaptureStateHelperInterface
{
    public function processOrderCapture(OrderEntity $order, Context $context): void;
}
