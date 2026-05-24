<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\OnsiteMessagingReplacer;

use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;

interface PlaceholderReplacerInterface
{
    public function replace(string $target, ProductPageLoadedEvent $event): string;
}
