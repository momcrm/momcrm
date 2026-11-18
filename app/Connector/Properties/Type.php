<?php

declare(strict_types=1);

namespace App\Connector\Properties;

use App\Connector\Types\Microsoft\MicrosoftConnector;
use Mom\Data\AbstractString;

/**
 * The key of the connector type the account was connected as, such as
 * "microsoft". The ConnectorRegistry knows what that type can do.
 */
final class Type extends AbstractString
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'type';
    }

    public static function forEloquentFactoryValue(): string
    {
        return MicrosoftConnector::KEY;
    }
}
