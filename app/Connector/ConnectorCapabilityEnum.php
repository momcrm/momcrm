<?php

declare(strict_types=1);

namespace App\Connector;

use App\Shared\Traits\Enum;
use Mom\Connectors\Mail\ProvidesMail;

/**
 * What a connector can be used for. A feature asks for the connectors that
 * have the capability it needs, and never for a particular type. A new
 * capability is a case here and a contract for connector types to implement;
 * AppServiceProvider registers each case with the connector registry.
 */
enum ConnectorCapabilityEnum: string
{
    use Enum;

    /**
     * Reading a mailbox and sending from it.
     */
    case Mail = 'mail';

    /**
     * The contract a connector type implements to have this capability.
     *
     * @return class-string
     */
    public function contract(): string
    {
        return match ($this) {
            self::Mail => ProvidesMail::class,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Mail => 'Email',
        };
    }
}
