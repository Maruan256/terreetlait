<?php

declare(strict_types=1);

namespace KlarnaPayment\Service\ScheduledTask\AuthorizeOrders;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class AuthorizeOrdersTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'klarna_payment.authorize_orders';
    }

    public static function getDefaultInterval(): int
    {
        return 3600; // 1 hour
    }
}
