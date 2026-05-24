<?php

declare(strict_types=1);

namespace KlarnaPayment\Core\Checkout\Cart\Error;

use Shopware\Core\Checkout\Cart\Error\Error;

class MissingStateError extends Error
{
    public const ID = '46415a610dee4faf8f2bcd704db2ef66';

    public function getId(): string
    {
        return self::ID;
    }

    public function getMessageKey(): string
    {
        return 'error.state';
    }

    public function getLevel(): int
    {
        return self::LEVEL_ERROR;
    }

    public function blockOrder(): bool
    {
        return true;
    }

    public function getParameters(): array
    {
        return [];
    }
}
