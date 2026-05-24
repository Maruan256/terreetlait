<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Response\GetOrder;

use KlarnaPayment\Components\Client\Response\GenericResponse;
use KlarnaPayment\Components\Client\Response\GetOrderResponse;
use Shopware\Core\Framework\Context;

interface GetOrderResponseHydratorInterface
{
    public function hydrate(GenericResponse $genericResponse, Context $context): GetOrderResponse;
}
