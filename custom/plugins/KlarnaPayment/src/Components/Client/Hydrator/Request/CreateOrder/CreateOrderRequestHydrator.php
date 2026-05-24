<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\CreateOrder;

use KlarnaPayment\Components\Client\Hydrator\Struct\Address\AddressStructHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Struct\Customer\CustomerStructHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Struct\Delivery\DeliveryStructHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Struct\LineItem\LineItemStructHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Struct\SalesTaxLineItem\SalesTaxLineItemStructHydratorInterface;
use KlarnaPayment\Components\Client\Request\CreateOrderRequest;
use KlarnaPayment\Components\Client\Struct\Attachment;
use KlarnaPayment\Components\Client\Struct\Options;
use KlarnaPayment\Components\ConfigReader\ConfigReaderInterface;
use KlarnaPayment\Components\Converter\CustomOrderConverter;
use KlarnaPayment\Components\Helper\OrderFetcherInterface;
use KlarnaPayment\Components\Helper\PaymentHelper\PaymentHelperInterface;
use KlarnaPayment\Components\Struct\ExtraMerchantData;
use KlarnaPayment\Installer\Modules\PaymentMethodInstaller;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Routing\RouterInterface;

class CreateOrderRequestHydrator implements CreateOrderRequestHydratorInterface
{
    /** @var LineItemStructHydratorInterface */
    private $lineItemHydrator;

    /** @var DeliveryStructHydratorInterface */
    private $deliveryHydrator;

    /** @var SalesTaxLineItemStructHydratorInterface */
    private $salesTaxLineItemHydrator;

    /** @var AddressStructHydratorInterface */
    private $addressHydrator;

    /** @var CustomerStructHydratorInterface */
    private $customerHydrator;

    /** @var PaymentHelperInterface */
    private $paymentHelper;

    /** @var CustomOrderConverter */
    private $orderConverter;

    /** @var OrderFetcherInterface */
    private $orderFetcher;

    /** @var RouterInterface */
    private $router;

    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(
        LineItemStructHydratorInterface $lineItemHydrator,
        DeliveryStructHydratorInterface $deliveryHydrator,
        SalesTaxLineItemStructHydratorInterface $salesTaxLineItemHydrator,
        AddressStructHydratorInterface $addressHydrator,
        CustomerStructHydratorInterface $customerHydrator,
        PaymentHelperInterface $paymentHelper,
        CustomOrderConverter $orderConverter,
        OrderFetcherInterface $orderFetcher,
        RouterInterface $router,
        ConfigReaderInterface $configReader
    ) {
        $this->lineItemHydrator         = $lineItemHydrator;
        $this->deliveryHydrator         = $deliveryHydrator;
        $this->salesTaxLineItemHydrator = $salesTaxLineItemHydrator;
        $this->addressHydrator          = $addressHydrator;
        $this->customerHydrator         = $customerHydrator;
        $this->paymentHelper            = $paymentHelper;
        $this->orderConverter           = $orderConverter;
        $this->orderFetcher             = $orderFetcher;
        $this->router                   = $router;
        $this->configReader             = $configReader;
    }

    public function hydrate(
        string $orderId,
        PaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $context
    ): CreateOrderRequest {
        $order = $this->orderFetcher->getOrderFromOrder($orderId, $context->getContext());

        if ($order === null) {
            throw new \LogicException('could not find order via id');
        }

        $cart = $this->orderConverter->convertOrderToCart($order, $context->getContext());

        $totalTaxAmount = $this->getTotalTaxAmount($cart->getPrice()->getCalculatedTaxes());

        $options = new Options();
        $options->assign([
            'disable_confirmation_modals' => true,
        ]);

        if ($order->getAddresses() === null) {
            throw new \LogicException('Order has no addresses');
        }

        /** @var OrderAddressEntity $billingAddress */
        $billingAddress = $order->getAddresses()->get($order->getBillingAddressId());

        if ($billingAddress === null) {
            throw new \LogicException('Order has no billing address');
        }

        if ($billingAddress->getCountry() === null) {
            throw new \LogicException('Address has no country');
        }

        if ($order->getOrderCustomer() === null) {
            throw new \LogicException('Order has no customer');
        }

        if ($order->getDeliveries() === null) {
            throw new \LogicException('Order has no deliveries');
        }

        $delivery = $order->getDeliveries()->first();

        $shippingAddress = $billingAddress;

        if ($delivery instanceof OrderDeliveryEntity) {
            $shippingAddress = $delivery->getShippingOrderAddress();
        }

        $request = new CreateOrderRequest();
        $request->assign([
            'authorizationToken' => $dataBag->get('klarnaAuthorizationToken'),
            'orderNumber'        => $order->getOrderNumber(),
            'purchaseCountry'    => $billingAddress->getCountry()->getIso(),
            'locale'             => substr_replace($this->paymentHelper->getSalesChannelLocale($context)->getCode(), (string) $billingAddress->getCountry()->getIso(), 3, 2),
            'purchaseCurrency'   => $context->getCurrency()->getIsoCode(),
            'options'            => $options,
            'orderAmount'        => $order->getPrice()->getTotalPrice(),
            'orderTaxAmount'     => $totalTaxAmount,
            'orderLines'         => $this->hydrateOrderLines($cart, $context, (string) $billingAddress->getCountry()->getIso()),
            'salesChannel'       => $context->getSalesChannel()->getId(),
            'merchantUrls'       => $this->getMerchantUrls($transaction),
            'billingAddress'     => $this->addressHydrator->hydrateFromOrderAddress($billingAddress, $order->getOrderCustomer()),
            // TODO: Only one shipping address is supported currently, this could change in the future
            'shippingAddress' => $this->addressHydrator->hydrateFromOrderAddress($shippingAddress, $order->getOrderCustomer()),
            'customer'        => $this->customerHydrator->hydrate($context),
        ]);

        $extraMerchantData = $this->getExtraMerchantData($dataBag, $context);

        if ($extraMerchantData->getMerchantData() !== null) {
            $request->assign(['merchantData' => $extraMerchantData->getMerchantData()]);
        }

        if ($extraMerchantData->getAttachment() !== null) {
            $attachment = new Attachment();
            $attachment->assign(['data' => $extraMerchantData->getAttachment()]);

            $request->assign(['attachment' => $attachment]);
        }

        return $request;
    }

    private function getTotalTaxAmount(CalculatedTaxCollection $taxes): float
    {
        $totalTaxAmount = 0;

        foreach ($taxes as $tax) {
            $totalTaxAmount += $tax->getTax();
        }

        return $totalTaxAmount;
    }

    /**
     * @return array<int,mixed>
     */
    private function hydrateOrderLines(Cart $cart, SalesChannelContext $salesChannelContext, string $iso): array
    {
        $orderLines = [];

        $currency = $salesChannelContext->getCurrency();
        $context  = $salesChannelContext->getContext();

        if ($iso === PaymentMethodInstaller::KLARNA_API_REGION_US) {
            // lineItems with net prices and sales-tax extracted as seperate lineitem for USA
            return $this->salesTaxLineItemHydrator->hydrate($cart->getLineItems(), $cart->getDeliveries(), $currency, $salesChannelContext);
        }

        foreach ($this->lineItemHydrator->hydrate($cart->getLineItems(), $currency, $salesChannelContext) as $orderLine) {
            $orderLines[] = $orderLine;
        }

        foreach ($this->deliveryHydrator->hydrate($cart->getDeliveries(), $currency, $context) as $orderLine) {
            $orderLines[] = $orderLine;
        }

        return array_filter($orderLines);
    }

    /**
     * @return string[]
     */
    private function getMerchantUrls(PaymentTransactionStruct $transaction): array
    {
        $notificationUrl = $this->router->generate(
            'widgets.klarna.callback.notification',
            [
                'transaction_id' => $transaction->getOrderTransactionId(),
            ],
            RouterInterface::ABSOLUTE_URL
        );

        $confirmationUrl = $transaction->getReturnUrl();

        return [
            'confirmation' => $confirmationUrl,
            'notification' => $notificationUrl,
        ];
    }

    private function getExtraMerchantData(RequestDataBag $dataBag, SalesChannelContext $context): ExtraMerchantData
    {
        $config            = $this->configReader->read($context->getSalesChannel()->getId());
        $extraMerchantData = new ExtraMerchantData();

        if (!$config->get('kpSendExtraMerchantData')) {
            return $extraMerchantData;
        }

        $customerData = $dataBag->get('klarnaCustomerData');

        if (empty($customerData)) {
            return $extraMerchantData;
        }

        $customerData = json_decode($customerData, true);

        if (!array_key_exists('merchant_data', $customerData)) {
            return $extraMerchantData;
        }

        $extraMerchantData->assign([
            'merchantData' => $customerData['merchant_data'],
        ]);

        $attachment = $customerData['attachment'];

        if (!empty($attachment)) {
            $attachment = json_decode($customerData['attachment']['body'], true);

            $extraMerchantData->assign([
                'attachment' => $attachment,
            ]);
        }

        return $extraMerchantData;
    }
}
