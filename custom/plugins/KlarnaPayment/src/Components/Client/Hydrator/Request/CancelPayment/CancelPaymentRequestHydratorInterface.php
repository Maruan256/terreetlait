<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\CancelPayment;

use KlarnaPayment\Components\Client\Request\CancelPaymentRequest;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

interface CancelPaymentRequestHydratorInterface
{
    public function hydrate(RequestDataBag $dataBag): CancelPaymentRequest;
}
