<?php

declare(strict_types=1);

namespace App\ArticleVersion\Properties;

use Mom\Data\AbstractDate;

final class SavedAt extends AbstractDate
{
    public static function getName(): string
    {
        return 'saved_at';
    }
}
