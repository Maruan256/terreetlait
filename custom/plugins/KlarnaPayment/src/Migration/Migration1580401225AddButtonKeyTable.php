<?php

declare(strict_types=1);

namespace KlarnaPayment\Migration;

use Doctrine\DBAL\Connection;
use KlarnaPayment\Components\Helper\BackwardsCompatibility\DbalConnectionHelper;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1580401225AddButtonKeyTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1580401225;
    }

    public function update(Connection $connection): void
    {
        DbalConnectionHelper::exec($connection, '
            CREATE TABLE IF NOT EXISTS `klarna_payment_button_key` (
                `id` BINARY(16) NOT NULL,

                `button_key` VARCHAR(255) NOT NULL,
                `sales_channel_domain_id` BINARY(16) NOT NULL,

                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,

                PRIMARY KEY (`id`, `button_key`),
                KEY `fk.klarna_payment_button_key.sales_channel_domain_id` (`sales_channel_domain_id`),
                CONSTRAINT `fk.klarna_payment_button_key.sales_channel_domain_id`
                    FOREIGN KEY (`sales_channel_domain_id`)
                    REFERENCES `sales_channel_domain` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
       ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
