<?php

declare(strict_types=1);

namespace App\EmailCode;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, EmailCode>
 */
final class EmailCodeCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return EmailCode::class;
    }
}
