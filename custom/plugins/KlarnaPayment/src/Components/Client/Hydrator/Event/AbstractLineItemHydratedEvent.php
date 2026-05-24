<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Event;

use KlarnaPayment\Components\Client\Struct\LineItem as KlarnaLineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Framework\Context;
use Symfony\Contracts\EventDispatcher\Event;

class AbstractLineItemHydratedEvent extends Event
{
    /** @var LineItem */
    private $lineItem;

    /** @var array<int,KlarnaLineItem> */
    private $hydratedLineItems;

    /** @var Context */
    private $context;

    /**
     * @param array<int,KlarnaLineItem> $hydratedLineItems
     */
    public function __construct(LineItem $lineItem, array $hydratedLineItems, Context $context)
    {
        $this->lineItem          = $lineItem;
        $this->hydratedLineItems = $hydratedLineItems;
        $this->context           = $context;
    }

    public function getLineItem(): LineItem
    {
        return $this->lineItem;
    }

    /**
     * @return array<int,KlarnaLineItem>
     */
    public function getHydratedLineItems(): array
    {
        return $this->hydratedLineItems;
    }

    /**
     * @param KlarnaLineItem[] $hydratedLineItems
     */
    public function setHydratedLineItems(array $hydratedLineItems): void
    {
        $this->hydratedLineItems = $hydratedLineItems;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
