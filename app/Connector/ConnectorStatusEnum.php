<?php

declare(strict_types=1);

namespace App\Connector;

use App\Shared\Traits\Enum;

enum ConnectorStatusEnum: string
{
    use Enum;

    case Connected = 'connected';

    /**
     * The outside service no longer accepts what we have stored, so someone
     * has to connect the account again before anything uses it.
     */
    case NeedsReconnect = 'needs_reconnect';
}
