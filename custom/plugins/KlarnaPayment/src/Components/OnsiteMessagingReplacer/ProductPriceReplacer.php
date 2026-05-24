<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\OnsiteMessagingReplacer;

use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;

class ProductPriceReplacer implements PlaceholderReplacerInterface
{
    private const REPLACE_NEEDLE = '{{productPrice}}';

    public function replace(string $onsiteMessagingSnippet, ProductPageLoadedEvent $event): string
    {
        if (strpos($onsiteMessagingSnippet, self::REPLACE_NEEDLE) !== false) {
            $prices = $event->getPage()->getProduct()->getPrice();

            if (!$prices) {
                return $onsiteMessagingSnippet;
            }

            if ($prices->count() > 0) {
                /** @var Price $curPrice */
                $curPrice               = $prices->first();
                $formattedPrice         = round($curPrice->getGross() * 100, 0);
                $onsiteMessagingSnippet = str_replace(
                    self::REPLACE_NEEDLE,
                    (string) $formattedPrice,
                    $onsiteMessagingSnippet
                );
            }
        }

        return $onsiteMessagingSnippet;
    }
}
