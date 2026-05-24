<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\UpdateAddress;

use KlarnaPayment\Components\Client\Request\UpdateAddressRequest;
use KlarnaPayment\Components\Client\Struct\Address;
use KlarnaPayment\Components\Exception\KlarnaOrderIdNotFoundException;
use KlarnaPayment\Installer\Modules\CustomFieldInstaller;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;

class UpdateAddressRequestHydrator implements UpdateAddressRequestHydratorInterface
{
    public function hydrate(OrderEntity $orderEntity, Context $context): UpdateAddressRequest
    {
        if ($orderEntity->getAddresses() === null || $orderEntity->getOrderCustomer() === null) {
            throw new \LogicException('could not find order via id');
        }

        $billingAddress = $orderEntity->getAddresses()->get($orderEntity->getBillingAddressId());

        if ($billingAddress === null) {
            throw new \LogicException('could not load billing address from order');
        }

        $shippingAddress = $this->getOrderShippingAddress($orderEntity);

        if ($shippingAddress === null) {
            $shippingAddress = $billingAddress;
        }

        $request = new UpdateAddressRequest();
        $request->assign([
            'orderId'         => $orderEntity->getId(),
            'klarnaOrderId'   => $this->getKlarnaOrderId($orderEntity),
            'salesChannel'    => $orderEntity->getSalesChannelId(),
            'billingAddress'  => $this->hydrateAddress($orderEntity->getOrderCustomer(), $billingAddress),
            'shippingAddress' => $this->hydrateAddress($orderEntity->getOrderCustomer(), $shippingAddress),
        ]);

        return $request;
    }

    private function hydrateAddress(OrderCustomerEntity $customer, OrderAddressEntity $customerAddress): Address
    {
        $address = new Address();

        $address->assign([
            'companyName'    => $customerAddress->getCompany(),
            'firstName'      => $customerAddress->getFirstName(),
            'lastName'       => $customerAddress->getLastName(),
            'postalCode'     => $customerAddress->getZipcode(),
            'streetAddress'  => $customerAddress->getStreet(),
            'streetAddress2' => $this->getStreetAddress2($customerAddress),
            'city'           => $customerAddress->getCity(),
            'country'        => $this->getCustomerCountry($customerAddress),
            'region'         => $customerAddress->getCountryState() instanceof CountryStateEntity ? $customerAddress->getCountryState()->getShortCode() : null,
            'email'          => $customer->getEmail(),
            'phoneNumber'    => $customerAddress->getPhoneNumber(),
        ]);

        return $address;
    }

    private function getStreetAddress2(OrderAddressEntity $customerAddress): ?string
    {
        $streetAddress2 = $customerAddress->getAdditionalAddressLine1();

        if (!empty($customerAddress->getAdditionalAddressLine2())) {
            $streetAddress2 .= ' - ' . $customerAddress->getAdditionalAddressLine2();
        }

        return $streetAddress2;
    }

    private function getCustomerCountry(OrderAddressEntity $customerAddress): string
    {
        $country = $customerAddress->getCountry();

        if ($country === null || $country->getIso() === null) {
            throw new \LogicException('missing order customer country');
        }

        return $country->getIso();
    }

    private function getOrderShippingAddress(OrderEntity $orderEntity): ?OrderAddressEntity
    {
        /** @var OrderDeliveryEntity[] $deliveries */
        $deliveries = $orderEntity->getDeliveries();

        // TODO: Only one shipping address is supported currently, this could change in the future
        foreach ($deliveries as $delivery) {
            if ($delivery->getShippingOrderAddress() === null) {
                continue;
            }

            return $delivery->getShippingOrderAddress();
        }

        return null;
    }

    private function getKlarnaOrderId(OrderEntity $orderEntity): string
    {
        if ($orderEntity->getTransactions() === null) {
            throw new KlarnaOrderIdNotFoundException();
        }

        foreach ($orderEntity->getTransactions() as $transaction) {
            if ($transaction->getStateMachineState() === null) {
                continue;
            }

            if ($transaction->getStateMachineState()->getTechnicalName() === OrderTransactionStates::STATE_CANCELLED) {
                continue;
            }

            if (empty($transaction->getCustomFields()[CustomFieldInstaller::FIELD_KLARNA_ORDER_ID])) {
                continue;
            }

            return $transaction->getCustomFields()[CustomFieldInstaller::FIELD_KLARNA_ORDER_ID];
        }

        throw new KlarnaOrderIdNotFoundException();
    }
}
