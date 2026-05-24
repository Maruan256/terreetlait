<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\DataAbstractionLayer\Entity\RequestLog;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                  add(RequestLogEntity $entity)
 * @method void                  set(string $key, RequestLogEntity $entity)
 * @method RequestLogEntity[]    getIterator()
 * @method RequestLogEntity[]    getElements()
 * @method null|RequestLogEntity get(string $key)
 * @method null|RequestLogEntity first()
 * @method null|RequestLogEntity last()
 */
class RequestLogCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return RequestLogEntity::class;
    }
}
