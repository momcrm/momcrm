<?php

declare(strict_types=1);

namespace App\SocialAccount;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, SocialAccount>
 */
final class SocialAccountCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return SocialAccount::class;
    }
}
