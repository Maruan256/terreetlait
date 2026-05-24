<?php

declare(strict_types=1);

namespace KlarnaPayment\Migration;

use Doctrine\DBAL\Connection;
use KlarnaPayment\Components\Helper\BackwardsCompatibility\DbalConnectionHelper;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1692606430AddCartData extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1692606430;
    }

    public function update(Connection $connection): void
    {
        DbalConnectionHelper::exec($connection, 'CREATE TABLE IF NOT EXISTS `klarna_cart` (
    `id` BINARY(16) NOT NULL,
    `cart_token` VARCHAR(50) NOT NULL,
    `payload` JSON NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.klarna_cart.payload` CHECK (JSON_VALID(`payload`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
