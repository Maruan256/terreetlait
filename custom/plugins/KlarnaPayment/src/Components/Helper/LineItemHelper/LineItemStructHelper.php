<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\LineItemHelper;

use KlarnaPayment\Components\Client\Hydrator\Struct\Delivery\DeliveryStructHydrator;
use KlarnaPayment\Components\Client\Struct\LineItem;
use NetInventors\NetiNextEasyCoupon\Core\Checkout\Cart\AbstractCartProcessor;
use NetInventors\NetiNextEasyCoupon\Struct\LineItemStruct;
use Shopware\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Shopware\Core\Checkout\Cart\LineItem\LineItem as CartLineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Promotion\Cart\PromotionProcessor;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Seo\SeoUrlPlaceholderHandlerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\Collection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\CustomizedProducts\Core\Checkout\CustomizedProductsCartDataCollector;

class LineItemStructHelper implements LineItemStructHelperInterface
{
    /** @var EntityRepository */
    private $productRepository;

    /** @var SeoUrlPlaceholderHandlerInterface */
    private $seoUrlPlaceholderHandler;

    /** @var EntityRepository */
    private $shippingMethodRepository;

    public function __construct(
        EntityRepository $productRepository,
        SeoUrlPlaceholderHandlerInterface $seoUrlPlaceholderHandler,
        EntityRepository $shippingMethodRepository
    ) {
        $this->productRepository        = $productRepository;
        $this->seoUrlPlaceholderHandler = $seoUrlPlaceholderHandler;
        $this->shippingMethodRepository = $shippingMethodRepository;
    }

    public function getTaxRate(CalculatedPrice $price): float
    {
        $taxRate = 0;

        foreach ($price->getCalculatedTaxes() as $tax) {
            if ($tax->getTaxRate() > $taxRate) {
                $taxRate = $tax->getTaxRate();
            }
        }

        return $taxRate;
    }

    public function getTotalTaxAmount(CalculatedTaxCollection $taxes): float
    {
        $totalTaxAmount = 0;

        foreach ($taxes as $tax) {
            $totalTaxAmount += $tax->getTax();
        }

        return $totalTaxAmount;
    }

    public function generateProductUrl(string $productId, SalesChannelContext $salesChannelContext): string
    {
        $salesChannel = $salesChannelContext->getSalesChannel();
        $domains      = $salesChannel->getDomains();
        $domainId     = method_exists($salesChannelContext, 'getDomainId')
            ? $salesChannelContext->getDomainId()
            : null;

        if ($domains === null) {
            return '';
        }

        $currentDomain = $domainId !== null && $domains->has($domainId)
            ? $domains->get($domainId)
            : $domains->first();

        if ($currentDomain === null) {
            return '';
        }

        return $this->seoUrlPlaceholderHandler->replace(
            $this->seoUrlPlaceholderHandler->generate(
                'frontend.detail.page',
                ['productId' => $productId]
            ),
            $currentDomain->getUrl(),
            $salesChannelContext
        );
    }

    public function getUnitNameFromProduct(ProductEntity $product): ?string
    {
        if ($product->getUnit() === null) {
            return null;
        }

        return $product->getUnit()->getTranslation('shortCode');
    }

    public function getReferenceNumber(CartLineItem $cartLineItem): string
    {
        if ($cartLineItem->hasPayloadValue('productNumber')) {
            $referenceNumber = $cartLineItem->getPayloadValue('productNumber');
        } else {
            $referenceNumber = (string) $cartLineItem->getReferencedId();
        }

        return mb_strimwidth($referenceNumber, 0, 64);
    }

    public function getLineItemType(CartLineItem $lineItem): string
    {
        $type = $lineItem->getType();

        if ($this->isEasyCouponPurchase($lineItem) === true) {
            return LineItem::TYPE_GIFT_CARD;
        }

        if ($type === CartLineItem::PRODUCT_LINE_ITEM_TYPE) {
            if(!empty($lineItem->getStates())){
                if(in_array('is-download', $lineItem->getStates())){
                    return LineItem::TYPE_DIGITAL;
                }
            }
            return LineItem::TYPE_PHYSICAL;
        }

        if ($type === CartLineItem::CREDIT_LINE_ITEM_TYPE) {
            return LineItem::TYPE_DISCOUNT;
        }

        if ($type === PromotionProcessor::LINE_ITEM_TYPE) {
            return LineItem::TYPE_DISCOUNT;
        }

        if ($this->isEasyCouponRedemption($type)) {
            return LineItem::TYPE_GIFT_CARD;
        }

        // TODO: Add surcharge as soon as Shopware supports it.

        return LineItem::TYPE_PHYSICAL;
    }

    public function loadProducts(Collection $lineItems, Context $context): EntityCollection
    {
        $products = $this->getReferenceIds($lineItems);

        if (empty($products)) {
            return new EntityCollection();
        }

        $context = clone $context;
        $context->setConsiderInheritance(true);

        $criteria = new Criteria($products);
        $criteria->addAssociation('unit');
        $criteria->addAssociation('categories');
        $criteria->addAssociation('manufacturer');

        $products = $this->productRepository->search($criteria, $context);

        if (!$products->count()) {
            return new EntityCollection();
        }

        return $products->getEntities();
    }

    public function isCustomProductLineItem(?string $lineItemType): bool
    {
        if (class_exists('Swag\CustomizedProducts\Core\Checkout\CustomizedProductsCartDataCollector') &&
            defined('Swag\CustomizedProducts\Core\Checkout\CustomizedProductsCartDataCollector::CUSTOMIZED_PRODUCTS_TEMPLATE_LINE_ITEM_TYPE') &&
            $lineItemType === CustomizedProductsCartDataCollector::CUSTOMIZED_PRODUCTS_TEMPLATE_LINE_ITEM_TYPE
        ) {
            return true;
        }

        return false;
    }

    public function getShippingMethodName(Delivery $delivery, Context $context): string
    {
        $criteria = (new Criteria([$delivery->getShippingMethod()->getId()]))
            ->addAssociation('translations')
            ->setLimit(1);

        /** @var ?ShippingMethodEntity $shippingMethod */
        $shippingMethod = $this->shippingMethodRepository->search($criteria, $context)->first();

        if ($shippingMethod === null || $shippingMethod->getTranslation('name') === null) {
            return DeliveryStructHydrator::NAME;
        }

        return $shippingMethod->getTranslation('name');
    }

    /**
     * @return array<array<string>|string>
     */
    private function getReferenceIds(?Collection $lineItems): array
    {
        $referenceIds = [];

        if ($lineItems === null) {
            return $referenceIds;
        }

        foreach ($lineItems as $lineItem) {
            if ($lineItem instanceof CartLineItem || $lineItem instanceof OrderLineItemEntity) {
                if ($this->isCustomProductLineItem($lineItem->getType())) {
                    $referenceIds = array_merge($referenceIds, $this->getReferenceIds($lineItem->getChildren()));

                    continue;
                }

                if ($lineItem->getType() === CartLineItem::PRODUCT_LINE_ITEM_TYPE) {
                    $referenceIds[] = $lineItem->getReferencedId();
                }
            }
        }

        return array_filter($referenceIds);
    }

    private function isEasyCouponRedemption(?string $lineItemType): bool
    {
        return class_exists('NetInventors\NetiNextEasyCoupon\Core\Checkout\Cart\AbstractCartProcessor') &&
            defined('NetInventors\NetiNextEasyCoupon\Core\Checkout\Cart\AbstractCartProcessor::EASY_COUPON_LINE_ITEM_TYPE') &&
            $lineItemType === AbstractCartProcessor::EASY_COUPON_LINE_ITEM_TYPE;
    }

    private function isEasyCouponPurchase(CartLineItem $lineItem): bool
    {
        if (!class_exists('NetInventors\NetiNextEasyCoupon\Struct\LineItemStruct')) {
            return false;
        }

        $voucherPayload = $lineItem->getPayloadValue(LineItemStruct::PAYLOAD_NAME);

        return is_array($voucherPayload) && is_float($voucherPayload['voucherValue']);
    }
}
