<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Struct\ProductIdentifier;

use KlarnaPayment\Components\Client\Struct\ProductIdentifier;
use Shopware\Core\Content\Product\ProductEntity;

interface ProductIdentifierStructHydratorInterface
{
    public function hydrate(ProductEntity $product): ProductIdentifier;
}
