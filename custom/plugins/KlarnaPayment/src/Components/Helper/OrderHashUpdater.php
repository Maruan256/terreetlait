<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper;

use KlarnaPayment\Components\Client\Request\UpdateOrderRequest;
use KlarnaPayment\Components\DataAbstractionLayer\Entity\Order\OrderExtension;
use KlarnaPayment\Components\DataAbstractionLayer\Entity\Order\OrderExtensionEntity;
use KlarnaPayment\Components\PaymentHandler\AbstractKlarnaPaymentHandler;
use KlarnaPayment\Core\Framework\ContextScope;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

class OrderHashUpdater
{
    /** @var EntityRepository */
    private $klarnaOrderExtensionRepository;

    /** @var RequestHasherInterface */
    private $updateOrderRequestHasher;

    public function __construct(
        EntityRepository $klarnaOrderExtensionRepository,
        RequestHasherInterface $updateOrderRequestHasher
    ) {
        $this->klarnaOrderExtensionRepository = $klarnaOrderExtensionRepository;
        $this->updateOrderRequestHasher       = $updateOrderRequestHasher;
    }

    public function updateOrderCartHash(UpdateOrderRequest $request, OrderEntity $orderEntity, Context $context): void
    {
        $orderExtensionEntity = $this->getExtension($orderEntity);

        $newHash = $this->updateOrderRequestHasher->getHash($request, AbstractKlarnaPaymentHandler::CART_HASH_CURRENT_VERSION);

        $orderExtensionEntity->setOrderCartHash($newHash);
        $orderExtensionEntity->setOrderCartHashVersion(AbstractKlarnaPaymentHandler::CART_HASH_CURRENT_VERSION);

        $context->scope(ContextScope::INTERNAL_SCOPE, function (Context $context) use ($orderExtensionEntity): void {
            $this->klarnaOrderExtensionRepository->upsert([$orderExtensionEntity->getAssign()], $context);
        });
    }

    public function saveOrderAddressHash(string $hash, OrderEntity $orderEntity, Context $context): void
    {
        $orderExtensionEntity = $this->getExtension($orderEntity);

        $orderExtensionEntity->setOrderAddressHash($hash);

        $context->scope(ContextScope::INTERNAL_SCOPE, function (Context $context) use ($orderExtensionEntity): void {
            $this->klarnaOrderExtensionRepository->upsert([$orderExtensionEntity->getAssign()], $context);
        });
    }

    private function getExtension(OrderEntity $orderEntity): OrderExtensionEntity
    {
        $orderExtensionEntity = $orderEntity->getExtension(OrderExtension::EXTENSION_NAME);

        if (!$orderExtensionEntity instanceof OrderExtensionEntity) {
            $orderExtensionEntity = new OrderExtensionEntity();
            $orderExtensionEntity->setId($orderEntity->getId());
            $orderExtensionEntity->setOrderId($orderEntity->getId());
        }

        return $orderExtensionEntity;
    }
}
