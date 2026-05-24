<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Event;

use KlarnaPayment\Components\Extension\SessionDataExtension;
use KlarnaPayment\Components\Struct\ExtraMerchantData;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\NestedEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class SetExtraMerchantDataEvent extends NestedEvent
{
    /** @var ExtraMerchantData */
    private $data;

    /** @var SessionDataExtension */
    private $sessionData;

    /** @var Cart */
    private $cart;

    /** @var SalesChannelContext */
    private $salesChannelContext;

    public function __construct(
        ExtraMerchantData $data,
        SessionDataExtension $sessionData,
        Cart $cart,
        SalesChannelContext $salesChannelContext
    ) {
        $this->data                = $data;
        $this->sessionData         = $sessionData;
        $this->cart                = $cart;
        $this->salesChannelContext = $salesChannelContext;
    }

    public function getData(): ExtraMerchantData
    {
        return $this->data;
    }

    public function getSessionData(): SessionDataExtension
    {
        return $this->sessionData;
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public function getContext(): Context
    {
        return $this->salesChannelContext->getContext();
    }
}
