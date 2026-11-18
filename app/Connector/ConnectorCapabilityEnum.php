<?php

declare(strict_types=1);

namespace App\Connector;

use App\Shared\Traits\Enum;

/**
 * What a connector can be used for. A feature asks for the connectors that
 * have the capability it needs, and never for a particular type.
 */
enum ConnectorCapabilityEnum: string
{
    use Enum;

    /**
     * Reading a mailbox and sending from it.
     */
    case Mail = 'mail';

    public function label(): string
    {
        return match ($this) {
            self::Mail => 'Email',
        };
    }
}
