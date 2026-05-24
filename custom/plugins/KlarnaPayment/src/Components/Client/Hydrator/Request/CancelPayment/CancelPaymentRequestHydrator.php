<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\CancelPayment;

use KlarnaPayment\Components\Client\Request\CancelPaymentRequest;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class CancelPaymentRequestHydrator implements CancelPaymentRequestHydratorInterface
{
    public function hydrate(RequestDataBag $dataBag): CancelPaymentRequest
    {
        $request = new CancelPaymentRequest();

        $request->assign([
            'orderId'       => $dataBag->get('order_id'),
            'klarnaOrderId' => $dataBag->get('klarna_order_id'),
            'salesChannel'  => $dataBag->get('salesChannel'),
        ]);

        return $request;
    }
}
