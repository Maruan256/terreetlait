<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Struct\SalesTaxLineItem;

use KlarnaPayment\Components\Client\Hydrator\Struct\Delivery\DeliveryStructHydrator;
use KlarnaPayment\Components\Client\Hydrator\Struct\ProductIdentifier\ProductIdentifierStructHydratorInterface;
use KlarnaPayment\Components\Client\Struct\LineItem;
use KlarnaPayment\Components\Helper\BackwardsCompatibility\DecimalPrecisionHelper;
use KlarnaPayment\Components\Helper\LineItemHelper\LineItemStructHelper;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem as CartLineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection as CartLineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class SalesTaxLineItemStructHydrator implements SalesTaxLineItemStructHydratorInterface
{
    /** @var ProductIdentifierStructHydratorInterface */
    private $productIdentifierHydrator;

    /** @var LineItemStructHelper */
    private $lineItemStructHelper;

    public function __construct(
        ProductIdentifierStructHydratorInterface $productIdentifierHydrator,
        LineItemStructHelper $lineItemStructHelper
    ) {
        $this->productIdentifierHydrator = $productIdentifierHydrator;
        $this->lineItemStructHelper      = $lineItemStructHelper;
    }

    public function hydrate(CartLineItemCollection $lineItems, DeliveryCollection $deliveries, CurrencyEntity $currency, SalesChannelContext $salesChannelContext): array
    {
        return $this->mergeHydratedLineItems([
            'shipping' => $this->hydrateShippingLineItems($deliveries, $currency, $salesChannelContext->getContext()),
            'product'  => $this->hydrateProductLineItems($lineItems, $currency, $salesChannelContext),
        ]);
    }

    private function mergeHydratedLineItems(array $hydratedLineItems): array
    {
        $lineItems         = array_merge($hydratedLineItems['product']['lineItems'], $hydratedLineItems['shipping']['lineItems']);
        $salesTaxLineItems = [];

        foreach ($hydratedLineItems['product']['salesTaxes'] as $key => $productSalesTax) {
            $salesTaxLineItem = new LineItem();

            if (!isset($hydratedLineItems['shipping']['salesTaxes'][$key])) {
                $salesTaxLineItem->assign(
                    [
                        'type'        => $productSalesTax['type'],
                        'name'        => $productSalesTax['name'],
                        'quantity'    => 1,
                        'unitPrice'   => $productSalesTax['unitPrice'],
                        'totalAmount' => $productSalesTax['totalAmount'],
                    ]
                );
                $salesTaxLineItems[] = $salesTaxLineItem;

                continue;
            }

            $salesTaxLineItem->assign(
                [
                    'type'        => $productSalesTax['type'],
                    'name'        => $productSalesTax['name'],
                    'quantity'    => 1,
                    'unitPrice'   => $productSalesTax['unitPrice'] + $hydratedLineItems['shipping']['salesTaxes'][$key]['unitPrice'],
                    'totalAmount' => $productSalesTax['totalAmount'] + $hydratedLineItems['shipping']['salesTaxes'][$key]['totalAmount'],
                ]
            );

            $salesTaxLineItems[] = $salesTaxLineItem;

            unset($hydratedLineItems['shipping']['salesTaxes'][$key]);
        }

        if (!empty($hydratedLineItems['shipping']['salesTaxes'])) {
            foreach ($hydratedLineItems['shipping']['salesTaxes'] as $shippingSalesTax) {
                $salesTaxLineItem = new LineItem();

                $salesTaxLineItem->assign(
                    [
                        'type'        => $shippingSalesTax['type'],
                        'name'        => $shippingSalesTax['name'],
                        'quantity'    => 1,
                        'unitPrice'   => $shippingSalesTax['unitPrice'],
                        'totalAmount' => $shippingSalesTax['totalAmount'],
                    ]
                );

                $salesTaxLineItems[] = $salesTaxLineItem;
            }
        }

        return array_merge($lineItems, $salesTaxLineItems);
    }

    private function hydrateProductLineItems(CartLineItemCollection $productLineItems, CurrencyEntity $currency, SalesChannelContext $salesChannelContext): array
    {
        $products          = $this->lineItemStructHelper->loadProducts($productLineItems, $salesChannelContext->getContext());
        $lineItems         = [];
        $salesTaxLineItems = [];

        foreach ($productLineItems as $item) {
            $lineItem = new LineItem();

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
                continue;
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
                $totalAmount    = $price->getTotalPrice();
                $unitPrice      = $price->getUnitPrice();

                if ($salesChannelContext->getContext()->getTaxState() !== CartPrice::TAX_STATE_NET) {
                    $totalAmount -= $totalTaxAmount;
                    $unitPrice -= round($totalTaxAmount / $item->getQuantity(), $decimalPrecision);
                }

                $lineItem->assign(
                    [
                        'unitPrice'   => round($unitPrice, $decimalPrecision),
                        'totalAmount' => round($totalAmount, $decimalPrecision),
                    ]
                );

                $lineItems[] = $lineItem;
                $taxRate     = $this->lineItemStructHelper->getTaxRate($price);

                if (isset($salesTaxLineItems[$taxRate])) {
                    $salesTaxLineItems[$taxRate]['unitPrice'] = round(
                        $salesTaxLineItems[$taxRate]['unitPrice'] + $totalTaxAmount,
                        $decimalPrecision
                    );
                    $salesTaxLineItems[$taxRate]['totalAmount'] = round(
                        $salesTaxLineItems[$taxRate]['totalAmount'] + $totalTaxAmount,
                        $decimalPrecision
                    );

                    continue;
                }

                $salesTaxLineItems[$taxRate] = [
                    'type'        => LineItem::TYPE_SALES_TAX,
                    'name'        => 'Tax ' . $taxRate . '%',
                    'quantity'    => 1,
                    'unitPrice'   => round($totalTaxAmount, $decimalPrecision),
                    'totalAmount' => round($totalTaxAmount, $decimalPrecision),
                ];
            }
        }

        return ['lineItems' => $lineItems, 'salesTaxes' => $salesTaxLineItems];
    }

    private function hydrateShippingLineItems(DeliveryCollection $deliveries, CurrencyEntity $currency, Context $context): array
    {
        $lineItems         = [];
        $salesTaxLineItems = [];

        foreach ($deliveries as $delivery) {
            if (empty($delivery->getShippingCosts()->getTotalPrice())) {
                continue;
            }

            $precision          = DecimalPrecisionHelper::getPrecision($currency);
            $shippingMethodName = $this->lineItemStructHelper->getShippingMethodName($delivery, $context);

            if ($context->getTaxState() === CartPrice::TAX_STATE_FREE) {
                $lineItem = new LineItem();
                $lineItem->assign([
                    'type'        => LineItem::TYPE_PHYSICAL,
                    'reference'   => DeliveryStructHydrator::NAME,
                    'name'        => $shippingMethodName,
                    'quantity'    => $delivery->getShippingCosts()->getQuantity(),
                    'unitPrice'   => $delivery->getShippingCosts()->getUnitPrice(),
                    'totalAmount' => $delivery->getShippingCosts()->getTotalPrice(),
                ]);

                $lineItems[] = $lineItem;
            }

            foreach ($delivery->getShippingCosts()->getCalculatedTaxes() as $tax) {
                $totalAmount = $tax->getPrice();
                $unitPrice   = $tax->getPrice();

                if ($context->getTaxState() !== CartPrice::TAX_STATE_NET) {
                    $totalAmount -= $tax->getTax();
                    $unitPrice -= $tax->getTax();
                }

                $lineItem = new LineItem();
                $lineItem->assign([
                    'type'        => LineItem::TYPE_PHYSICAL,
                    'reference'   => DeliveryStructHydrator::NAME,
                    'name'        => $shippingMethodName,
                    'quantity'    => $delivery->getShippingCosts()->getQuantity(),
                    'unitPrice'   => round($unitPrice, 2),
                    'totalAmount' => round($totalAmount, 2),
                ]);

                $lineItems[] = $lineItem;

                if (isset($salesTaxLineItems[$tax->getTaxRate()])) {
                    $salesTaxLineItems[$tax->getTaxRate()]['unitPrice']   = round($salesTaxLineItems[$tax->getTaxRate()]['unitPrice'] + $tax->getTax(), $precision);
                    $salesTaxLineItems[$tax->getTaxRate()]['totalAmount'] = round($salesTaxLineItems[$tax->getTaxRate()]['totalAmount'] + $tax->getTax(), $precision);

                    continue;
                }

                $salesTaxLineItems[$tax->getTaxRate()] = [
                    'type'        => LineItem::TYPE_SALES_TAX,
                    'name'        => 'Tax ' . $tax->getTaxRate() . '%',
                    'quantity'    => 1,
                    'unitPrice'   => round($tax->getTax(), 2),
                    'totalAmount' => round($tax->getTax(), 2),
                ];
            }
        }

        return ['lineItems' => $lineItems, 'salesTaxes' => $salesTaxLineItems];
    }
}
