<?php

declare(strict_types=1);

namespace KlarnaPayment\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class OrderUpdateDeniedException extends ShopwareHttpException
{
    public function __construct(string $id)
    {
        parent::__construct('Klarna does not approve of update for order: {{ input }}', ['input' => $id]);
    }

    public function getErrorCode(): string
    {
        return 'KLARNAPAYMENT__ORDER_UPDATE_DENIED';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
