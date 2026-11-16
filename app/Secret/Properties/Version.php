<?php

declare(strict_types=1);

namespace App\Secret\Properties;

use Mom\Data\AbstractInteger;

/**
 * Counts changes to the secret's values: 1 when made, one more each time they
 * change. The values themselves are never on the data object.
 */
final class Version extends AbstractInteger
{
    public static bool $includeInDatabaseCreate = false;

    public static bool $includeInDatabaseUpdate = false;

    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'version';
    }
}
