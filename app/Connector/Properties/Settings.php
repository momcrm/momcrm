<?php

declare(strict_types=1);

namespace App\Connector\Properties;

use Mom\Data\AbstractCollection;

/**
 * What the connector needs to know that is not secret, such as whether its
 * sign-in covers shared mailboxes. Secrets are kept apart, encrypted.
 */
final class Settings extends AbstractCollection
{
    public static function getName(): string
    {
        return 'settings';
    }
}
