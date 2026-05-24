<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\EventListener;

use KlarnaPayment\Components\ConfigReader\ConfigReaderInterface;
use KlarnaPayment\Components\Helper\PaymentHelper\PaymentHelperInterface;
use KlarnaPayment\Components\Struct\Configuration;
use KlarnaPayment\Components\Struct\PaymentMethodBadge;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Pagelet\Footer\FooterPageletLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class FooterBadgeEventListener implements EventSubscriberInterface
{
    public function __construct(private ConfigReaderInterface $configReader, private PaymentHelperInterface $paymentHelper)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FooterPageletLoadedEvent::class => 'addFooterBadge',
        ];
    }

    public function addFooterBadge(FooterPageletLoadedEvent $event): void
    {
        $context = $event->getSalesChannelContext();
        $config = $this->configReader->read($context->getSalesChannel()->getId());

        if ($this->displayKlarnaPaymentsBadge($config, $context)) {
            $badge = new PaymentMethodBadge();
            $badge->assign([
                'paymentType' => PaymentMethodBadge::TYPE_PAYMENTS,
            ]);

            $event->getPagelet()->addExtension(PaymentMethodBadge::EXTENSION_NAME, $badge);
        }
    }

    private function displayKlarnaPaymentsBadge(Configuration $configuration, SalesChannelContext $context): bool
    {
        if (!$configuration->get('kpDisplayFooterBadge')) {
            return false;
        }

        if (!$this->paymentHelper->isKlarnaPaymentsEnabled($context)) {
            return false;
        }

        return true;
    }
}
