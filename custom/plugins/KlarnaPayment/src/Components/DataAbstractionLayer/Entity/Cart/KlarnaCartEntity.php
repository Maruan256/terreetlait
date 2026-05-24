<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\DataAbstractionLayer\Entity\Cart;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;

class KlarnaCartEntity extends Entity
{
    /** @var string */
    protected $cartToken;

    /** @var array */
    protected $payload;

    public function getCartToken(): string
    {
        return $this->cartToken;
    }

    public function setCartToken(string $cartToken): void
    {
        $this->cartToken = $cartToken;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }
}
