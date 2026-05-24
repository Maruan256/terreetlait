<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\ReleaseRemainingAuthorization;

use KlarnaPayment\Components\Client\Request\ReleaseRemainingAuthorizationRequest;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class ReleaseRemainingAuthorizationHydrator implements ReleaseRemainingAuthorizationHydratorInterface
{
    public function hydrate(RequestDataBag $dataBag): ReleaseRemainingAuthorizationRequest
    {
        $request = new ReleaseRemainingAuthorizationRequest();

        $request->assign([
            'orderId'       => $dataBag->get('order_id'),
            'klarnaOrderId' => $dataBag->get('klarna_order_id'),
            'salesChannel'  => $dataBag->get('salesChannel'),
        ]);

        return $request;
    }
}
