<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Struct\ProductIdentifier;

use KlarnaPayment\Components\Client\Struct\ProductIdentifier;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\ProductEntity;

class ProductIdentifierStructHydrator implements ProductIdentifierStructHydratorInterface
{
    public function hydrate(ProductEntity $product): ProductIdentifier
    {
        $identifier = new ProductIdentifier();
        $identifier->assign([
            'categoryPath'           => $this->buildCategoryPath($product),
            'globalTradeItemNumber'  => $product->getEan(),
            'manufacturerPartNumber' => $product->getManufacturerNumber(),
        ]);

        if ($product->getManufacturer() !== null) {
            $identifier->assign([
                'brand' => $product->getManufacturer()->getTranslation('name') ?? $product->getManufacturer()->getName(),
            ]);
        }

        return $identifier;
    }

    private function buildCategoryPath(ProductEntity $product): ?string
    {
        if ($product->getCategories() === null) {
            return null;
        }

        /** @var null|CategoryEntity $category */
        $category = $product->getCategories()->first();

        if ($category === null || empty($category->getBreadcrumb())) {
            return null;
        }

        $breadcrumbs = array_slice($category->getBreadcrumb(), 1);

        return implode(' > ', $breadcrumbs);
    }
}
