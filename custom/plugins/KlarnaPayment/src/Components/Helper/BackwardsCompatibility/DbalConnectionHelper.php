<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\BackwardsCompatibility;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class DbalConnectionHelper
{
    /**
     * @return false|mixed
     * @throws Exception
     */
    public static function fetchColumn(Connection $connection, string $query, array $params = []): mixed
    {
        /** @phpstan-ignore-next-line */
        return $connection->fetchOne($query, $params);
    }

    /**
     * @param Connection $connection
     * @param string $query
     * @return int|string
     * @throws Exception
     */
    public static function exec(Connection $connection, string $query): int|string
    {
        /** @phpstan-ignore-next-line */
        return $connection->executeStatement($query);
    }
}
