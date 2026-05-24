<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\GetOrder;

use KlarnaPayment\Components\Client\Request\GetOrderRequest;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

interface GetOrderRequestHydratorInterface
{
    public function hydrate(RequestDataBag $dataBag): GetOrderRequest;
}
