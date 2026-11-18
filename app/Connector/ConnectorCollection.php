<?php

declare(strict_types=1);

namespace App\Connector;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Connector>
 */
final class ConnectorCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Connector::class;
    }
}
