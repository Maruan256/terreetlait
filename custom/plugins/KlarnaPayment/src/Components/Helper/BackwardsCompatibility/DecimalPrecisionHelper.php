<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\BackwardsCompatibility;

use Shopware\Core\System\Currency\CurrencyEntity;

class DecimalPrecisionHelper
{
    public static function getPrecision(CurrencyEntity $currencyEntity): int
    {
        return $currencyEntity->getItemRounding()->getDecimals();
    }
}
