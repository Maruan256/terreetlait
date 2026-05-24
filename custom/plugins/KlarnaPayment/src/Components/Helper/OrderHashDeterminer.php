<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper;

use KlarnaPayment\Components\DataAbstractionLayer\Entity\Order\OrderExtension;
use KlarnaPayment\Components\DataAbstractionLayer\Entity\Order\OrderExtensionEntity;
use KlarnaPayment\Installer\Modules\CustomFieldInstaller;
use Shopware\Core\Checkout\Order\OrderEntity;

class OrderHashDeterminer
{
    public static function getOrderAddressHash(OrderEntity $orderEntity): ?string
    {
        $klarnaExtension = $orderEntity->getExtension(OrderExtension::EXTENSION_NAME);

        if ($klarnaExtension instanceof OrderExtensionEntity && !empty($klarnaExtension->getOrderAddressHash())) {
            return $klarnaExtension->getOrderAddressHash();
        }

        $customFields = $orderEntity->getCustomFields() ?? [];

        return $customFields[CustomFieldInstaller::FIELD_KLARNA_ORDER_ADDRESS_HASH] ?? null;
    }

    public static function getOrderCartHash(OrderEntity $orderEntity): ?string
    {
        $klarnaExtension = $orderEntity->getExtension(OrderExtension::EXTENSION_NAME);

        if ($klarnaExtension instanceof OrderExtensionEntity && !empty($klarnaExtension->getOrderCartHash())) {
            return $klarnaExtension->getOrderCartHash();
        }

        $customFields = $orderEntity->getCustomFields() ?? [];

        return $customFields[CustomFieldInstaller::FIELD_KLARNA_ORDER_CART_HASH] ?? null;
    }

    public static function getOrderCartHashVersion(OrderEntity $orderEntity): ?int
    {
        $klarnaExtension = $orderEntity->getExtension(OrderExtension::EXTENSION_NAME);

        if ($klarnaExtension instanceof OrderExtensionEntity && !empty($klarnaExtension->getOrderCartHashVersion())) {
            return $klarnaExtension->getOrderCartHashVersion();
        }

        $customFields = $orderEntity->getCustomFields() ?? [];

        if (!empty($customFields[CustomFieldInstaller::FIELD_KLARNA_ORDER_CART_HASH_VERSION])) {
            return (int) $customFields[CustomFieldInstaller::FIELD_KLARNA_ORDER_CART_HASH_VERSION];
        }

        return null;
    }
}
