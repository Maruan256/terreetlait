<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\CartHasher;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface CartHasherInterface
{
    public function generate(Cart $cart, SalesChannelContext $context): string;

    public function validate(Cart $cart, string $cartHash, SalesChannelContext $context): bool;
}
