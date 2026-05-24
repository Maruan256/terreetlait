<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\DataAbstractionLayer\Entity\Cart;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                  add(KlarnaCartEntity $entity)
 * @method void                  set(string $key, KlarnaCartEntity $entity)
 * @method KlarnaCartEntity[]    getIterator()
 * @method KlarnaCartEntity[]    getElements()
 * @method null|KlarnaCartEntity get(string $key)
 * @method null|KlarnaCartEntity first()
 * @method null|KlarnaCartEntity last()
 */
class KlarnaCartCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return KlarnaCartEntity::class;
    }
}
