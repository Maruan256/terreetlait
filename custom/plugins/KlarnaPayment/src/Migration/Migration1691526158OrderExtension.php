<?php

declare(strict_types=1);

namespace KlarnaPayment\Migration;

use Doctrine\DBAL\Connection;
use KlarnaPayment\Components\Helper\BackwardsCompatibility\DbalConnectionHelper;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1691526158OrderExtension extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1691526158;
    }

    public function update(Connection $connection): void
    {
        DbalConnectionHelper::exec($connection, 'CREATE TABLE IF NOT EXISTS `klarna_order_extension` (
                        `id` BINARY(16) NOT NULL,
                        `version_id` BINARY(16) NOT NULL,
                        `order_id` BINARY(16) NOT NULL,
                        `order_version_id` BINARY(16) NOT NULL,
                        `order_address_hash` VARCHAR(128) NULL,
                        `order_cart_hash` VARCHAR(128) NULL,
                        `order_cart_hash_version` INT(11) NULL,

                        `created_at` DATETIME(3) NOT NULL,
                        `updated_at` DATETIME(3) DEFAULT NULL,
                        PRIMARY KEY(`id`, `version_id`),
                        CONSTRAINT `fk.klarna_order_extension.order_id`
                            FOREIGN KEY (`order_id`, `order_version_id`) REFERENCES `order` (`id`, `version_id`)
                                ON DELETE CASCADE ON UPDATE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
