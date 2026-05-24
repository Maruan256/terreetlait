<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\EventListener;

use KlarnaPayment\Components\ConfigReader\ConfigReaderInterface;
use KlarnaPayment\Components\Extension\TemplateData\SignInWithKlarnaDataExtension;
use KlarnaPayment\Components\Helper\PaymentHelper\PaymentHelperInterface;

use Shopware\Storefront\Event\StorefrontRenderEvent;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class SignInWithKlarnaButtonEventListener implements EventSubscriberInterface
{
    /** @var PaymentHelperInterface */
    private $paymentHelper;

    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(
        PaymentHelperInterface $paymentHelper,
        ConfigReaderInterface $configReader
    ) {
        $this->paymentHelper      = $paymentHelper;
        $this->configReader       = $configReader;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            StorefrontRenderEvent::class => 'configurationData'
        ];
    }

    public function configurationData(StorefrontRenderEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();

        $configuration = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());

        if (!$configuration->get('isSignInWithKlarnaActive', false)) {
            return;
        }

        $templateData = new SignInWithKlarnaDataExtension(
            $configuration->get('signInWithKlarnaClientKey', ''),
            $configuration->get('signInWithKlarnaTheme', 'default'),
            $configuration->get('signInWithKlarnaShape', 'default'),
            implode(' ', $configuration->get('signInWithKlarnaDataKeys', []))
        );

        $event->setParameter(SignInWithKlarnaDataExtension::EXTENSION_NAME, $templateData);
    }
}
