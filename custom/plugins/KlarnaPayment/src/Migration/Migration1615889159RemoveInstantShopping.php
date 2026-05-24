<?php

declare(strict_types=1);

namespace KlarnaPayment\Migration;

use Doctrine\DBAL\Connection;
use KlarnaPayment\Components\Helper\BackwardsCompatibility\DbalConnectionHelper;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1615889159RemoveInstantShopping extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1615889159;
    }

    public function update(Connection $connection): void
    {
        DbalConnectionHelper::exec($connection, 'DROP TABLE IF EXISTS klarna_payment_button_key;');
        DbalConnectionHelper::exec($connection, "DELETE FROM system_config WHERE configuration_key IN (
            'KlarnaPayment.settings.instantShoppingType',
            'KlarnaPayment.settings.instantShoppingEnabled',
            'KlarnaPayment.settings.instantShoppingVariation',
            'KlarnaPayment.settings.termsCategory'
            );
        ");
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
