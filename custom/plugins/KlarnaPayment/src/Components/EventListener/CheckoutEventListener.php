<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\EventListener;

use KlarnaPayment\Components\Helper\PaymentHelper\PaymentHelperInterface;
use KlarnaPayment\Core\Checkout\Cart\Error\MissingStateError;
use KlarnaPayment\Installer\Modules\PaymentMethodInstaller;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class CheckoutEventListener implements EventSubscriberInterface
{
    public function __construct(private PaymentHelperInterface $paymentHelper, private EntityRepository $orderAddressRepository)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => ['validateCountryState'],
            CheckoutFinishPageLoadedEvent::class => ['addOrderBillingAddress'],
        ];
    }

    public function addOrderBillingAddress(CheckoutFinishPageLoadedEvent $event): void
    {
        $order = $event->getPage()->getOrder();
        $orderAddresses = $order->getAddresses() ?? new OrderAddressCollection();
        $billingAddressId = $order->getBillingAddressId();

        if ($orderAddresses->has($billingAddressId)) {
            return;
        }

        $billingAddress = $this->orderAddressRepository->search((new Criteria([$billingAddressId]))->addAssociation('country'), $event->getContext())->first();

        $orderAddresses->set($billingAddressId, $billingAddress);
        $order->setAddresses($orderAddresses);
    }

    public function validateCountryState(CheckoutConfirmPageLoadedEvent $event): void
    {
        if (!$this->paymentHelper->isKlarnaPaymentsEnabled($event->getSalesChannelContext())) {
            return;
        }

        if (!$this->paymentHelper->isKlarnaPaymentsSelected($event->getSalesChannelContext())) {
            return;
        }

        $customer = $event->getSalesChannelContext()->getCustomer();

        if ($customer === null) {
            return;
        }

        if ($activeBillingAddress = $customer->getActiveBillingAddress()) {
            if ($activeBillingAddress->getCountryStateId() &&
                $activeBillingAddress->getCountry()?->getIso() === PaymentMethodInstaller::KLARNA_API_REGION_US) {
                $errors = $event->getPage()->getCart()->getErrors();
                $errors->add(new MissingStateError());

                $event->getPage()->getCart()->setErrors($errors);
            }
        }
    }
}
