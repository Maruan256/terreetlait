<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Struct\Delivery;

use KlarnaPayment\Components\Client\Struct\LineItem;
use KlarnaPayment\Components\Helper\LineItemHelper\LineItemStructHelper;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\Currency\CurrencyEntity;

class DeliveryStructHydrator implements DeliveryStructHydratorInterface
{
    public const NAME = 'SHIPPING_COSTS';

    /** @var LineItemStructHelper */
    private $lineItemStructHelper;

    public function __construct(LineItemStructHelper $lineItemStructHelper)
    {
        $this->lineItemStructHelper = $lineItemStructHelper;
    }

    /**
     * @return LineItem[]
     */
    public function hydrate(DeliveryCollection $deliveries, CurrencyEntity $currency, Context $context): array
    {
        $lineItems = [];

        foreach ($deliveries as $delivery) {
            if (empty($delivery->getShippingCosts()->getTotalPrice())) {
                continue;
            }

            $shippingMethodName = $this->lineItemStructHelper->getShippingMethodName($delivery, $context);

            if ($context->getTaxState() === CartPrice::TAX_STATE_FREE) {
                $lineItem = new LineItem();
                $lineItem->assign([
                    'type'        => LineItem::TYPE_SHIPPING_FEE,
                    'reference'   => self::NAME,
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

                if ($context->getTaxState() === CartPrice::TAX_STATE_NET) {
                    $totalAmount += $tax->getTax();
                    $unitPrice += $tax->getTax();
                }

                $lineItem = new LineItem();
                $lineItem->assign([
                    'type'           => LineItem::TYPE_SHIPPING_FEE,
                    'reference'      => self::NAME,
                    'name'           => $shippingMethodName,
                    'quantity'       => $delivery->getShippingCosts()->getQuantity(),
                    'unitPrice'      => $unitPrice,
                    'totalAmount'    => $totalAmount,
                    'totalTaxAmount' => $tax->getTax(),
                    'taxRate'        => $tax->getTaxRate(),
                ]);

                $lineItems[] = $lineItem;
            }
        }

        return $lineItems;
    }
}
