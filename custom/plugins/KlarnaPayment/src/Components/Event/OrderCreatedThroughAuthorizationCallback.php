<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Event;

use Symfony\Contracts\EventDispatcher\Event;

class OrderCreatedThroughAuthorizationCallback extends Event
{
    public const EVENT_NAME = 'klarna.order.placed-trough-authorization';

    public function getName(): string
    {
        return self::EVENT_NAME;
    }
}
