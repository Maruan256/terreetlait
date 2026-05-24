<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Struct\LineItem;

use KlarnaPayment\Components\Client\Hydrator\Event\FilterCustomProductLineItemHydratedEvent;
use KlarnaPayment\Components\Client\Hydrator\Event\FilterProductLineItemHydratedEvent;
use KlarnaPayment\Components\Client\Hydrator\Struct\ProductIdentifier\ProductIdentifierStructHydratorInterface;
use KlarnaPayment\Components\Client\Struct\LineItem;
use KlarnaPayment\Components\Helper\BackwardsCompatibility\DecimalPrecisionHelper;
use KlarnaPayment\Components\Helper\LineItemHelper\LineItemStructHelper;
use Shopware\Core\Checkout\Cart\LineItem\LineItem as CartLineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection as CartLineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class LineItemStructHydrator implements LineItemStructHydratorInterface
{
    /** @var ProductIdentifierStructHydratorInterface */
    private $productIdentifierHydrator;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var LineItemStructHelper */
    private $lineItemStructHelper;

    public function __construct(
        ProductIdentifierStructHydratorInterface $productIdentifierHydrator,
        EventDispatcherInterface $eventDispatcher,
        LineItemStructHelper $lineItemStructHelper
    ) {
        $this->productIdentifierHydrator = $productIdentifierHydrator;
        $this->eventDispatcher           = $eventDispatcher;
        $this->lineItemStructHelper      = $lineItemStructHelper;
    }

    public function hydrate(CartLineItemCollection $lineItems, CurrencyEntity $currency, SalesChannelContext $salesChannelContext): array
    {
        $products = $this->lineItemStructHelper->loadProducts($lineItems, $salesChannelContext->getContext());

        $result = [];

        foreach ($lineItems as $item) {
            /** @var CartLineItem $item */
            if ($this->lineItemStructHelper->isCustomProductLineItem($item->getType())) {
                $hydratedLineItems = $this->buildLineItemStructsForCustomProduct($item, $products, $currency, $salesChannelContext);

                $lineItemHydratedEvent = new FilterCustomProductLineItemHydratedEvent($item, $hydratedLineItems, $salesChannelContext->getContext());
                $this->eventDispatcher->dispatch($lineItemHydratedEvent);

                $result = array_merge($result, $lineItemHydratedEvent->getHydratedLineItems());

                continue;
            }

            $hydratedLineItems = $this->buildLineItemStructsForCartItem($item, $products, $currency, $salesChannelContext);

            $lineItemHydratedEvent = new FilterProductLineItemHydratedEvent($item, $hydratedLineItems, $salesChannelContext->getContext());
            $this->eventDispatcher->dispatch($lineItemHydratedEvent);

            $result = array_merge($result, $lineItemHydratedEvent->getHydratedLineItems());
        }

        return $result;
    }

    private function buildLineItemStructsForCustomProduct(CartLineItem $item, EntityCollection $products, CurrencyEntity $currency, SalesChannelContext $salesChannelContext): array
    {
        $customProductItems = $item->getChildren();
        $result             = [];

        foreach ($customProductItems as $customProductItem) {
            /** @var CartLineItem $customProductItem */
            $result = array_merge($result, $this->buildLineItemStructsForCartItem($customProductItem, $products, $currency, $salesChannelContext));

            if ($customProductItem->getChildren()->count() > 0) {
                $result = array_merge($result, $this->buildLineItemStructsForCustomProduct($customProductItem, $products, $currency, $salesChannelContext));
            }
        }

        return $result;
    }

    private function buildLineItemStructsForCartItem(CartLineItem $item, EntityCollection $products, CurrencyEntity $currency, SalesChannelContext $salesChannelContext): array
    {
        $lineItems = [];
        $lineItem  = new LineItem();

        $lineItem->assign(
            [
                'type'      => $this->lineItemStructHelper->getLineItemType($item),
                'reference' => $this->lineItemStructHelper->getReferenceNumber($item),
                'name'      => $item->getLabel(),
                'quantity'  => $item->getQuantity(),
            ]
        );

        if ($item->getCover() !== null) {
            $lineItem->assign(
                [
                    'imageUrl' => $item->getCover()->getUrl(),
                ]
            );
        }

        if ($item->getType() === CartLineItem::PRODUCT_LINE_ITEM_TYPE) {
            /** @var null|ProductEntity $product */
            $product = $products->get((string) $item->getReferencedId());

            if ($product !== null) {
                $lineItem->assign(
                    [
                        'productId'         => $product->getId(),
                        'quantityUnit'      => $this->lineItemStructHelper->getUnitNameFromProduct($product),
                        'productIdentifier' => $this->productIdentifierHydrator->hydrate($product),
                        'productUrl'        => $this->lineItemStructHelper->generateProductUrl(
                            $product->getId(),
                            $salesChannelContext
                        ),
                    ]
                );
            }
        }

        $price = $item->getPrice();

        if ($price === null) {
            return [];
        }

        $decimalPrecision = DecimalPrecisionHelper::getPrecision($currency);

        if ($price->getCalculatedTaxes()->count() > 1) {
            foreach ($price->getCalculatedTaxes() as $calculatedTax) {
                $splitLineItem = clone $lineItem;

                $totalAmount    = round($calculatedTax->getPrice(), $decimalPrecision);
                $unitPrice      = round($calculatedTax->getPrice() / $item->getQuantity(), $decimalPrecision);
                $totalTaxAmount = $calculatedTax->getTax();

                $splitLineItem->assign(
                    [
                        'unitPrice'      => $unitPrice,
                        'totalAmount'    => $totalAmount,
                        'totalTaxAmount' => $totalTaxAmount,
                        'taxRate'        => $calculatedTax->getTaxRate(),
                    ]
                );

                $lineItems[] = $splitLineItem;
            }
        } else {
            $totalTaxAmount = $this->lineItemStructHelper->getTotalTaxAmount($price->getCalculatedTaxes());

            $totalAmount = $price->getTotalPrice();
            $unitPrice   = $price->getUnitPrice();

            if ($salesChannelContext->getContext()->getTaxState() === CartPrice::TAX_STATE_NET) {
                $totalAmount += $totalTaxAmount;
                $unitPrice += round($totalTaxAmount / $item->getQuantity(), $decimalPrecision);
            }

            $lineItem->assign(
                [
                    'unitPrice'      => $unitPrice,
                    'totalAmount'    => $totalAmount,
                    'totalTaxAmount' => $totalTaxAmount,
                    'taxRate'        => $this->lineItemStructHelper->getTaxRate($price),
                ]
            );

            $lineItems[] = $lineItem;
        }

        return $lineItems;
    }
}
