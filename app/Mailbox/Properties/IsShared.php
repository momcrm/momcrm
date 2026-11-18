<?php

declare(strict_types=1);

namespace App\Mailbox\Properties;

use Mom\Data\AbstractBoolean;

/**
 * Whether this is a shared mailbox, reached through the person who connected
 * it, and not that person's own mailbox.
 */
final class IsShared extends AbstractBoolean
{
    public static function getName(): string
    {
        return 'is_shared';
    }

    public function default(): bool
    {
        return false;
    }
}
