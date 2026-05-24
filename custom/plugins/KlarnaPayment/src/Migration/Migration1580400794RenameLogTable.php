<?php

declare(strict_types=1);

namespace KlarnaPayment\Migration;

use Doctrine\DBAL\Connection;
use KlarnaPayment\Components\Helper\BackwardsCompatibility\DbalConnectionHelper;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1580400794RenameLogTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1580400794;
    }

    public function update(Connection $connection): void
    {
        $result = DbalConnectionHelper::fetchColumn($connection, 'SHOW TABLES LIKE \'klarna_payment_request_log\';');

        if (!$result) {
            DbalConnectionHelper::exec($connection, '
                RENAME TABLE klarna_payment_log to klarna_payment_request_log;
            ');
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
