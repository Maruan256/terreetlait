<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Factory;

use KlarnaPayment\Components\ConfigReader\ConfigReaderInterface;
use KlarnaPayment\Components\Event\SetExtraMerchantDataEvent;
use KlarnaPayment\Components\Extension\SessionDataExtension;
use KlarnaPayment\Components\Struct\ExtraMerchantData;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MerchantDataFactory implements MerchantDataFactoryInterface
{
    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(ConfigReaderInterface $configReader, EventDispatcherInterface $eventDispatcher)
    {
        $this->configReader    = $configReader;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getExtraMerchantData(
        SessionDataExtension $sessionData,
        Cart $cart,
        SalesChannelContext $context
    ): ExtraMerchantData {
        $config = $this->configReader->read($context->getSalesChannel()->getId());
        $data   = new ExtraMerchantData();

        if ($config->get('kpSendExtraMerchantData')) {
            $this->eventDispatcher->dispatch(
                new SetExtraMerchantDataEvent($data, $sessionData, $cart, $context)
            );
        }

        return $data;
    }
}
