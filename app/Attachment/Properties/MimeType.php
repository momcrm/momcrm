<?php

declare(strict_types=1);

namespace App\Attachment\Properties;

use Mom\Data\AbstractString;

final class MimeType extends AbstractString
{
    public static function getName(): string
    {
        return 'mime_type';
    }
}
