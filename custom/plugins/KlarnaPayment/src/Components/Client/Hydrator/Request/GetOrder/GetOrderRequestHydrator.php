<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\GetOrder;

use KlarnaPayment\Components\Client\Request\GetOrderRequest;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class GetOrderRequestHydrator implements GetOrderRequestHydratorInterface
{
    public function hydrate(RequestDataBag $dataBag): GetOrderRequest
    {
        $request = new GetOrderRequest();

        $request->assign([
            'orderId'       => $dataBag->get('order_id'),
            'klarnaOrderId' => $dataBag->get('klarna_order_id'),
            'salesChannel'  => $dataBag->get('salesChannel'),
        ]);

        return $request;
    }
}
