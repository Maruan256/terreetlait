<?php

declare(strict_types=1);

namespace KlarnaPayment\Migration;

use Doctrine\DBAL\Connection;
use KlarnaPayment\Components\Helper\BackwardsCompatibility\DbalConnectionHelper;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1570840015AddLogTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1570840015;
    }

    public function update(Connection $connection): void
    {
        $result = DbalConnectionHelper::fetchColumn($connection, 'SHOW TABLES LIKE \'klarna_payment_request_log\';');

        if (!$result) {
            DbalConnectionHelper::exec($connection, '
            CREATE TABLE IF NOT EXISTS `klarna_payment_log` (
                `id` BINARY(16) NOT NULL,

                `klarna_order_id` VARCHAR(255) NOT NULL,
                `call_type` VARCHAR(255) NOT NULL,
                `request` JSON NOT NULL,
                `response` JSON NOT NULL,
                `idempotency_key` VARCHAR(255) NOT NULL,

                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,

                PRIMARY KEY (`id`),

                CONSTRAINT `json.klarna_payment_log.request` CHECK (JSON_VALID(`request`)),
                CONSTRAINT `json.klarna_payment_log.response` CHECK (JSON_VALID(`response`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
       ');
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
