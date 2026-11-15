<?php

declare(strict_types=1);

namespace App\Attachment\Properties;

use App\Shared\Abstracts\AbstractRelatedData;
use App\User\User as UserData;

/**
 * Who uploaded the file, when loaded.
 *
 * @extends AbstractRelatedData<UserData>
 */
final class Uploader extends AbstractRelatedData
{
    public static function getName(): string
    {
        return 'uploader';
    }

    public static function dataClass(): string
    {
        return UserData::class;
    }
}
