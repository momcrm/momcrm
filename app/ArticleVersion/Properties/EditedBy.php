<?php

declare(strict_types=1);

namespace App\ArticleVersion\Properties;

use App\Shared\Abstracts\AbstractRelatedData;
use App\User\User as UserData;

/**
 * Who saved this version, when loaded.
 *
 * @extends AbstractRelatedData<UserData>
 */
final class EditedBy extends AbstractRelatedData
{
    public static function getName(): string
    {
        return 'editedBy';
    }

    public static function dataClass(): string
    {
        return UserData::class;
    }
}
