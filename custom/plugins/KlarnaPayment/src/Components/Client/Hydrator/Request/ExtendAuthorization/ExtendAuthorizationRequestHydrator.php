<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\ExtendAuthorization;

use KlarnaPayment\Components\Client\Request\ExtendAuthorizationRequest;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class ExtendAuthorizationRequestHydrator implements ExtendAuthorizationRequestHydratorInterface
{
    public function hydrate(RequestDataBag $dataBag): ExtendAuthorizationRequest
    {
        $request = new ExtendAuthorizationRequest();

        $request->assign([
            'orderId'       => $dataBag->get('order_id'),
            'klarnaOrderId' => $dataBag->get('klarna_order_id'),
        ]);

        return $request;
    }
}
