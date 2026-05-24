<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\EventListener;

use KlarnaPayment\Components\ConfigReader\ConfigReaderInterface;
use KlarnaPayment\Components\Extension\TemplateData\OnsiteMessagingDataExtension;
use KlarnaPayment\Components\OnsiteMessagingReplacer\PlaceholderReplacerInterface;
use KlarnaPayment\Components\Validator\OnsiteMessagingValidator;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TemplateDataEventListener implements EventSubscriberInterface
{
    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var PlaceholderReplacerInterface */
    private $productPriceReplacer;

    /** @var OnsiteMessagingValidator */
    private $onsiteMessagingValidator;

    public function __construct(
        ConfigReaderInterface $configReader,
        PlaceholderReplacerInterface $productPriceReplacer,
        OnsiteMessagingValidator $onsiteMessagingValidator
    ) {
        $this->configReader             = $configReader;
        $this->productPriceReplacer     = $productPriceReplacer;
        $this->onsiteMessagingValidator = $onsiteMessagingValidator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => [
                ['addOnsiteTemplateData', 100],
            ],
        ];
    }

    public function addOnsiteTemplateData(ProductPageLoadedEvent $event): void
    {
        $pluginConfig           = $this->configReader->read($event->getSalesChannelContext()->getSalesChannel()->getId());
        $isActive               = (bool) $pluginConfig->get('isOnsiteMessagingActive');
        $onsiteMessagingSnippet = (string) $pluginConfig->get('onsiteMessagingSnippet');
        $onsiteMessagingScript  = (string) $pluginConfig->get('onsiteMessagingScript');

        if (!$this->onsiteMessagingValidator->isValid($isActive, $onsiteMessagingSnippet, $onsiteMessagingScript)) {
            return;
        }

        $onsiteMessagingSnippet = $this->productPriceReplacer->replace($onsiteMessagingSnippet, $event);

        $templateData = new OnsiteMessagingDataExtension([
            'klarnaOnsiteMessagingSnippet' => preg_replace("/\r|\n/", '', $onsiteMessagingSnippet),
            'klarnaOnsiteMessagingScript'  => $onsiteMessagingScript,
        ]);

        $event->getPage()->addExtension(OnsiteMessagingDataExtension::EXTENSION_NAME, $templateData);
    }
}
