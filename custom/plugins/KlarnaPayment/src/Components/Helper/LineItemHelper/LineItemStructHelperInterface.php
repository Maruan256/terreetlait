<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\LineItemHelper;

use Shopware\Core\Checkout\Cart\LineItem\LineItem as CartLineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\Struct\Collection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface LineItemStructHelperInterface
{
    public function getTaxRate(CalculatedPrice $price): float;

    public function getTotalTaxAmount(CalculatedTaxCollection $taxes): float;

    public function generateProductUrl(string $productId, SalesChannelContext $salesChannelContext): string;

    public function getUnitNameFromProduct(ProductEntity $product): ?string;

    public function getReferenceNumber(CartLineItem $cartLineItem): string;

    public function getLineItemType(CartLineItem $lineItem): string;

    public function loadProducts(Collection $lineItems, Context $context): EntityCollection;

    public function isCustomProductLineItem(?string $lineItemType): bool;
}
