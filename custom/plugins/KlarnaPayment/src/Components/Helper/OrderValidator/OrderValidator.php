<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\OrderValidator;

use KlarnaPayment\Components\Client\ClientInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\GetOrder\GetOrderRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\UpdateAddress\UpdateAddressRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\UpdateOrder\UpdateOrderRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Response\GetOrder\GetOrderResponseHydratorInterface;
use KlarnaPayment\Components\Exception\GetKlarnaOrderException;
use KlarnaPayment\Components\Exception\KlarnaOrderIdNotFoundException;
use KlarnaPayment\Components\Helper\OrderHashDeterminer;
use KlarnaPayment\Components\Helper\OrderHashUpdater;
use KlarnaPayment\Components\Helper\RequestHasherInterface;
use KlarnaPayment\Components\PaymentHandler\AbstractKlarnaPaymentHandler;
use KlarnaPayment\Installer\Modules\CustomFieldInstaller;
use Monolog\Logger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use KlarnaPayment\Components\Client\Response\GetOrderResponse;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use KlarnaPayment\Components\ConfigReader\ConfigReader;

class OrderValidator implements OrderValidatorInterface
{
    public const PREVIEWS_ORDER_MAPPING = 'klarna_previews_order_mapping';

    /** @var UpdateAddressRequestHydratorInterface */
    private $updateAddressRequestHydrator;

    /** @var UpdateOrderRequestHydratorInterface */
    private $updateOrderRequestHydrator;

    /** @var ClientInterface */
    private $client;

    /** @var RequestHasherInterface */
    private $updateOrderRequestHasher;

    /** @var RequestHasherInterface */
    private $updateAddressRequestHasher;

    /** @var OrderHashUpdater */
    private $orderHashUpdater;

    /** @var GetOrderRequestHydratorInterface */
    private $getOrderRequestHydrator;

    /** @var GetOrderResponseHydratorInterface */
    private $getOrderResponseHydrator;

    /** @var Logger */
    private $logger;

    /** @var ConfigReader */
    private $configReader;

    /**
     * This is a runtime cache to save requests for the same order.
     *
     * @var array<string,bool>
     */
    private $cachedCaputuredStates = [];

    public function __construct(
        UpdateAddressRequestHydratorInterface $updateAddressRequestHydrator,
        UpdateOrderRequestHydratorInterface $updateOrderRequestHydrator,
        ClientInterface $client,
        RequestHasherInterface $updateOrderRequestHasher,
        RequestHasherInterface $updateAddressRequestHasher,
        OrderHashUpdater $orderHashUpdater,
        GetOrderRequestHydratorInterface $getOrderRequestHydrator,
        GetOrderResponseHydratorInterface $getOrderResponseHydrator,
        Logger $logger,
        ConfigReader $configReader
    ) {
        $this->updateAddressRequestHydrator = $updateAddressRequestHydrator;
        $this->updateOrderRequestHydrator = $updateOrderRequestHydrator;
        $this->client = $client;
        $this->updateOrderRequestHasher = $updateOrderRequestHasher;
        $this->updateAddressRequestHasher = $updateAddressRequestHasher;
        $this->orderHashUpdater = $orderHashUpdater;
        $this->getOrderRequestHydrator = $getOrderRequestHydrator;
        $this->getOrderResponseHydrator = $getOrderResponseHydrator;
        $this->logger = $logger;
        $this->configReader = $configReader;
    }

    public function isKlarnaOrder(OrderEntity $orderEntity): bool
    {
        return !empty($this->getKlarnaOrderId($orderEntity));
    }

    /**
     * @throws GetKlarnaOrderException
     * @throws KlarnaOrderIdNotFoundException
     */
    public function validateAndInitLineItemsHash(OrderEntity $orderEntity, Context $context): bool
    {
        $request = $this->updateOrderRequestHydrator->hydrate($orderEntity, $context);
        $currentHash = OrderHashDeterminer::getOrderCartHash($orderEntity);

        if (!empty($currentHash)) {
            $hashVersion = OrderHashDeterminer::getOrderCartHashVersion($orderEntity) ?? AbstractKlarnaPaymentHandler::CART_HASH_DEFAULT_VERSION;
            $hash = $this->updateOrderRequestHasher->getHash($request, $hashVersion);

            if ($hash === $currentHash) {
                if ($hashVersion !== AbstractKlarnaPaymentHandler::CART_HASH_CURRENT_VERSION) {
                    $this->orderHashUpdater->updateOrderCartHash($request, $orderEntity, $context);
                }

                return true;
            }

            $this->logger->debug('Validate LineItems Hash: ', [
                'request' => $request,
                'currentHash' => $currentHash,
                'hash' => $hash

            ]);
        }

        if ($this->hasCapturedAmount($orderEntity, $context)) {
            $this->orderHashUpdater->updateOrderCartHash($request, $orderEntity, $context);

            return true;
        }

        $response = $this->client->request($request, $context);

        if ($response->getHttpStatus() === 204) {
            $this->orderHashUpdater->updateOrderCartHash($request, $orderEntity, $context);

            return true;
        }

        return false;
    }

    /**
     * @throws GetKlarnaOrderException
     * @throws KlarnaOrderIdNotFoundException
     */
    public function validateAndInitOrderAddressHash(OrderEntity $orderEntity, OrderEntity|null $previousOrder, Context $context, array &$errorArray = []): bool
    {
        // Check 1
        // perform the standard hash check from Kellerkinder

        $updateAddressRequest = $this->updateAddressRequestHydrator->hydrate($orderEntity, $context);
        $hash = $this->updateAddressRequestHasher->getHash($updateAddressRequest);
        $currentHash = OrderHashDeterminer::getOrderAddressHash($orderEntity);

        if (!empty($currentHash) && hash_equals($hash, $currentHash)) {
            return true;
        }

        $this->logger->debug('Validate Order-Address Hash Check 1: ', [
            'currentHash' => $currentHash,
            'hash' => $hash,
            'updateAddressRequest' => $updateAddressRequest
        ]);

        // Check 2
        // if preview shipping address  == new shipping address, then return true otherwise do check 3
        // new logic check for changes between shipping addres before saving in shopware and after

        if (!empty($previousOrder)) {
            $previousOrder = $this->collectAddressData($previousOrder);
            $currentOrder = $this->collectAddressData($orderEntity);

            if (hash_equals($this->getAddressHash($previousOrder), $this->getAddressHash($currentOrder))) {
                return true;
            }

            $this->logger->debug('Validate Order-Address Hash Check 2: ', [
                'previousOrder' => $previousOrder,
                'currentOrder' => $currentOrder
            ]);
        }

        // Check 3
        // if new shipping address == klarna shipping adress, then return true otherwise continue with validation
        // new logic check for changes between klarna API Address and Shopware Address

        $klarnaOrder = $this->getOrderFromKlarna($orderEntity, $context);

        $swShippingAddress = $this->collectAddressData($orderEntity);
        $klarnaShippingAddress = $this->collectKlarnaAddressData($klarnaOrder);

        if (hash_equals($this->getAddressHash($swShippingAddress), $this->getAddressHash($klarnaShippingAddress))) {
            return true;
        }

        $this->logger->debug('Validate Order-Address Hash Check 3: ', [
            'klarnaOrder' => $klarnaOrder,
            'swShippingAddress' => $swShippingAddress,
            'klarnaShippingAddress' => $klarnaShippingAddress
        ]);

        // klarna dashboard validation
        $updateAddressRequest->assign(['billingAddress' => null]);
        $response = $this->client->request($updateAddressRequest, $context);

        if ($response->getHttpStatus() === 204) {
            $this->orderHashUpdater->saveOrderAddressHash($hash, $orderEntity, $context);

            return true;
        }

        $configuration = $this->configReader->read($orderEntity->getSalesChannelId());

        // skip address validation check
        if ($configuration->get('klarnaDisableAddressValidation', false)) {
            $this->logger->info('Order-Address validation disabled: ', [
                "shopware_shipping_address" => $swShippingAddress,
                "klarna_shipping_address" => $klarnaShippingAddress,
                "klarnaDisabledAddressValidation" => $configuration->get('klarnaDisableAddressValidation', false)
            ]);

            return true;
        }

        $errorArray = [
            'message' => $response->getResponse(),
            'params' => [
                'shopware_shipping_address' => $swShippingAddress,
                'klarna_shipping_address' => $klarnaShippingAddress,
                'hashed_shopware_shipping_address' => $this->getAddressHash($swShippingAddress),
                'hashed_klarna_shipping_address' => $this->getAddressHash($klarnaShippingAddress)
            ]
        ];

        return false;
    }

    private function collectAddressData(OrderEntity $orderEntity): array
    {
        if ($orderEntity->getAddresses() === null || $orderEntity->getOrderCustomer() === null) {
            throw new \LogicException('could not find order address or order customer');
        }

        $orderCustomer = $orderEntity->getOrderCustomer();
        $shippingAddress = $this->getOrderShippingAddress($orderEntity);

        if (empty($shippingAddress)) {
            return [];
        }

        return [
            'companyName' => trim($shippingAddress->getCompany() ?? ''),
            'firstName' => trim($shippingAddress->getFirstName() ?? ''),
            'lastName' => trim($shippingAddress->getLastName() ?? ''),
            'postalCode' => trim($shippingAddress->getZipcode() ?? ''),
            'streetAddress' => trim($shippingAddress->getStreet() ?? ''),
            'streetAddress2' => trim($shippingAddress->getAdditionalAddressLine1() ?? ''),
            'city' => trim($shippingAddress->getCity() ?? ''),
            'region' => $shippingAddress->getCountryState() instanceof CountryStateEntity ? $shippingAddress->getCountryState()->getShortCode() : null,
            'country' => $shippingAddress->getCountryId() ? $shippingAddress->getCountryId() : null,
            'email' => trim($orderCustomer->getEmail() ?? ''),
            'phoneNumber' => trim($shippingAddress->getPhoneNumber() ?? '')
        ];
    }

    private function collectKlarnaAddressData(GetOrderResponse $klarnaOrder): array
    {
        if (!$klarnaOrder) {
            throw new \LogicException('could not find klarna order');
        }

        $shippingAddress = $klarnaOrder->getShippingAddress();
        if (empty($shippingAddress)) {
            return [];
        }

        return [
            'companyName' => trim($shippingAddress->getCompanyName() ?? ''),
            'firstName' => trim($shippingAddress->getFirstName() ?? ''),
            'lastName' => trim($shippingAddress->getLastName() ?? ''),
            'postalCode' => trim($shippingAddress->getPostalCode() ?? ''),
            'streetAddress' => trim($shippingAddress->getStreetAddress() ?? ''),
            'streetAddress2' => trim($shippingAddress->getStreetAddress2() ?? ''),
            'city' => trim($shippingAddress->getCity() ?? ''),
            'region' => $shippingAddress->getRegion() ? trim($shippingAddress->getRegion()) : null,
            'country' => $shippingAddress->getCountry() ? $shippingAddress->getCountry() : null,
            'email' => trim($shippingAddress->getEmail() ?? ''),
            'phoneNumber' => trim($shippingAddress->getPhoneNumber() ?? '')
        ];
    }

    /**
     * @throws GetKlarnaOrderException
     * @throws KlarnaOrderIdNotFoundException
     */
    private function getOrderFromKlarna(OrderEntity $orderEntity, Context $context): GetOrderResponse
    {
        $klarnaOrderId = $this->getKlarnaOrderId($orderEntity);

        if (empty($klarnaOrderId)) {
            throw new KlarnaOrderIdNotFoundException();
        }

        $dataBag = new RequestDataBag();
        $dataBag->add([
            'order_id' => $orderEntity->getId(),
            'klarna_order_id' => $klarnaOrderId,
            'salesChannel' => $orderEntity->getSalesChannelId(),
        ]);

        $orderRequest = $this->getOrderRequestHydrator->hydrate($dataBag);

        $response = $this->client->request($orderRequest, $context);

        if ($response->getHttpStatus() !== 200) {
            throw new GetKlarnaOrderException((string)$response->getHttpStatus(), $response->getResponse());
        }

        return $this->getOrderResponseHydrator->hydrate($response, $context);
    }

    private function getAddressHash(array $shippingAddress): string
    {
        $json = \json_encode($shippingAddress);

        if (empty($json)) {
            throw new \LogicException('could not generate hash');
        }

        return \hash('sha256', $json);
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

    /**
     * This method returns the KlarnaOrderId from the last transaction of a given order.
     * @param OrderEntity $orderEntity
     * @return string|null
     */
    private function getKlarnaOrderId(OrderEntity $orderEntity): ?string
    {
        if ($transaction = $orderEntity->getTransactions()?->last()) {
            if ($transaction->getStateMachineState()?->getTechnicalName() === OrderTransactionStates::STATE_CANCELLED) {
                return null;
            }

            if (empty($transaction->getCustomFields()[CustomFieldInstaller::FIELD_KLARNA_ORDER_ID])) {
                return null;
            }

            return $transaction->getCustomFieldsValue(CustomFieldInstaller::FIELD_KLARNA_ORDER_ID);
        }

        return null;
    }

    /**
     * @throws GetKlarnaOrderException
     * @throws KlarnaOrderIdNotFoundException
     */
    private function hasCapturedAmount(OrderEntity $orderEntity, Context $context): bool
    {
        $klarnaOrderId = $this->getKlarnaOrderId($orderEntity);

        if (empty($klarnaOrderId)) {
            throw new KlarnaOrderIdNotFoundException();
        }

        if (!array_key_exists($klarnaOrderId, $this->cachedCaputuredStates)) {
            $this->cachedCaputuredStates[$klarnaOrderId] = $this->hasCapturedAmountRequest($klarnaOrderId, $orderEntity, $context);
        }

        return $this->cachedCaputuredStates[$klarnaOrderId];
    }

    /**
     * @throws GetKlarnaOrderException
     */
    private function hasCapturedAmountRequest(string $klarnaOrderId, OrderEntity $orderEntity, Context $context): bool
    {
        $dataBag = new RequestDataBag();
        $dataBag->add([
            'order_id' => $orderEntity->getId(),
            'klarna_order_id' => $klarnaOrderId,
            'salesChannel' => $orderEntity->getSalesChannelId(),
        ]);

        $request = $this->getOrderRequestHydrator->hydrate($dataBag);

        $response = $this->client->request($request, $context);

        if ($response->getHttpStatus() !== 200) {
            $this->logger->debug('Capture Amount Request: ', [
                'getOrderRequest' => $request,
                'getOrderResponse' => $response,
                'status' => $response->getHttpStatus()
            ]);

            throw new GetKlarnaOrderException((string)$response->getHttpStatus(), $response->getResponse());
        }

        $order = $this->getOrderResponseHydrator->hydrate($response, $context);

        return $order->getCapturedAmount() > 0;
    }
}
