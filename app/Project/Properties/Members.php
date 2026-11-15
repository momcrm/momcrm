<?php

declare(strict_types=1);

namespace App\Project\Properties;

use App\Shared\Abstracts\AbstractRelatedDataCollection;
use App\User\UserCollection;

/**
 * The project's members, when loaded.
 */
final class Members extends AbstractRelatedDataCollection
{
    public static function getName(): string
    {
        return 'members';
    }

    public static function collectionClass(): string
    {
        return UserCollection::class;
    }

    public function toDataCollection(): UserCollection
    {
        $collection = parent::toDataCollection();

        return $collection instanceof UserCollection ? $collection : new UserCollection;
    }
}
