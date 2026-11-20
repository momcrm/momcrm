<?php

declare(strict_types=1);

namespace App\Article\Properties;

use App\Shared\Abstracts\AbstractRelatedData;
use App\User\User as UserData;

/**
 * Who wrote the article, when loaded.
 *
 * @extends AbstractRelatedData<UserData>
 */
final class Author extends AbstractRelatedData
{
    public static function getName(): string
    {
        return 'author';
    }

    public static function dataClass(): string
    {
        return UserData::class;
    }
}
