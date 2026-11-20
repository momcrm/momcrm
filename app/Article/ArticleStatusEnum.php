<?php

declare(strict_types=1);

namespace App\Article;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

/**
 * Whether an article is still being written or is ready to be relied on.
 * Archiving is separate (archived_at), so an article keeps its status there.
 */
enum ArticleStatusEnum: string
{
    use Enum;
    use EnumOptions;

    case Draft = 'draft';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
        };
    }
}
