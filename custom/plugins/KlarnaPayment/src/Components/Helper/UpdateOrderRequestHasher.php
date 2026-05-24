<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper;

use KlarnaPayment\Components\Client\Request\RequestInterface;
use KlarnaPayment\Components\Client\Request\UpdateOrderRequest;
use KlarnaPayment\Components\Client\Struct\LineItem;

class UpdateOrderRequestHasher implements RequestHasherInterface
{
    /** @var string */
    private $appSecret;

    public function __construct(string $appSecret)
    {
        $this->appSecret = $appSecret;
    }

    public function getHash(RequestInterface $request, int $version = 1): string
    {
        if (empty($this->appSecret)) {
            throw new \LogicException('empty app secret');
        }

        if ($request instanceof UpdateOrderRequest && $version === 2) {
            $request = $this->getHashSanitizedUpdateOrderRequestObject($request);
        }

        $json = \json_encode($request, JSON_PRESERVE_ZERO_FRACTION);

        if (empty($json)) {
            throw new \LogicException('could not generate hash');
        }

        return \hash_hmac('sha256', $json, $this->appSecret);
    }

    private function getHashSanitizedUpdateOrderRequestObject(UpdateOrderRequest $request): UpdateOrderRequest
    {
        $clonedRequest = clone $request;

        $lineItems = $clonedRequest->getLineItems();
        usort($lineItems, static function (LineItem $a, LineItem $b) {
            return strcmp($a->getReference(), $b->getReference());
        });

        $newLineItems = [];

        foreach ($lineItems as $lineItem) {
            $newLineItem = new LineItem();
            $newLineItem->assign([
                'type'        => $lineItem->getType(),
                'reference'   => $lineItem->getReference(),
                'name'        => $lineItem->getName(),
                'quantity'    => $lineItem->getQuantity(),
                'totalAmount' => $lineItem->getTotalAmount(),
            ]);

            $newLineItems[] = $newLineItem;
        }

        $clonedRequest->assign(['lineItems' => $newLineItems]);

        return $clonedRequest;
    }
}
