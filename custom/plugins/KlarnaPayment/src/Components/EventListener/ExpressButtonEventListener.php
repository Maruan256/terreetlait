<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\EventListener;

use KlarnaPayment\Components\ConfigReader\ConfigReaderInterface;
use KlarnaPayment\Components\Controller\Storefront\KlarnaExpressCheckoutController;
use KlarnaPayment\Components\Extension\TemplateData\ExpressDataExtension;
use KlarnaPayment\Components\Helper\PaymentHelper\PaymentHelperInterface;
use KlarnaPayment\Installer\Modules\PaymentMethodInstaller;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Checkout\Customer\Event\GuestCustomerRegisterEvent;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannel\AbstractContextSwitchRoute;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

readonly class ExpressButtonEventListener implements EventSubscriberInterface
{

    public function __construct(
        private PaymentHelperInterface $paymentHelper,
        private ConfigReaderInterface $configReader,
        private AbstractContextSwitchRoute $contextSwitchRoute,
        private RequestStack $requestStack
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutRegisterPageLoadedEvent::class => 'addExpressTemplateData',
            CheckoutCartPageLoadedEvent::class => 'addExpressTemplateData',
            OffcanvasCartPageLoadedEvent::class => 'addExpressTemplateData',
            ProductPageLoadedEvent::class => 'addExpressTemplateData',
            GuestCustomerRegisterEvent::class => 'changeDefaultPaymentMethod',
            CheckoutConfirmPageLoadedEvent::class => 'changePaymentMethod',
            CheckoutOrderPlacedEvent::class => 'resetSession',
        ];
    }

    /**
     * @param CheckoutCartPageLoadedEvent|CheckoutRegisterPageLoadedEvent|OffcanvasCartPageLoadedEvent $event
     */
    public function addExpressTemplateData(PageLoadedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();

        if (!$this->paymentHelper->isKlarnaExpressCheckoutEnabled($salesChannelContext)) {
            return;
        }

        $configuration = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());

        if (!$configuration->get('isKlarnaExpressCheckoutActive', false)) {
            return;
        }

        $templateData = new ExpressDataExtension(
            $configuration->get('klarnaExpressCheckoutClientKey', ''),
            $configuration->get('klarnaExpressTheme', 'default'),
            $configuration->get('klarnaExpressShape', 'default')
        );

        $event->getPage()->addExtension(ExpressDataExtension::EXTENSION_NAME, $templateData);
    }

    public function changePaymentMethod(CheckoutConfirmPageLoadedEvent $event): void
    {
        $context = $event->getSalesChannelContext();

        if (!$this->getSession()?->has(KlarnaExpressCheckoutController::KLARNA_EXPRESS_SESSION_KEY)) {
            return;
        }

        $customer = $context->getCustomer();

        if ($customer === null) {
            return;
        }

        $confirmPage = $event->getPage();
        $paymentMethods = $confirmPage->getPaymentMethods();

        /** @var PaymentMethodCollection $filtered */
        $filtered = $paymentMethods->filterByProperty('id', PaymentMethodInstaller::KLARNA_EXPRESS_CHECKOUT);

        $confirmPage->setPaymentMethods($filtered);
    }

    public function changeDefaultPaymentMethod(GuestCustomerRegisterEvent $event): void
    {
        if (!$this->getSession()?->has(KlarnaExpressCheckoutController::KLARNA_EXPRESS_SESSION_KEY)) {
            return;
        }

        $this->contextSwitchRoute->switchContext(
            new RequestDataBag([
                SalesChannelContextService::PAYMENT_METHOD_ID => PaymentMethodInstaller::KLARNA_EXPRESS_CHECKOUT,
            ]),
            $event->getSalesChannelContext()
        );
    }

    public function resetSession(): void
    {
        $this->getSession()?->remove(KlarnaExpressCheckoutController::KLARNA_EXPRESS_SESSION_KEY);
    }

    private function getSession(): ?SessionInterface
    {
        /**
         * Check if we're in HTTP request context before accessing session
         * In CLI/cronjob/message queue context, there is no request and thus no session
         */
        if (!$this->requestStack->getMainRequest()) {
            return null;
        }

        try {
            return $this->requestStack->getSession();
        } catch (SessionNotFoundException) {
            return null;
        }
    }
}
