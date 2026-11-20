<?php

declare(strict_types=1);

namespace App\ArticleLink\Properties;

use App\ArticleLink\ArticleLinkTargetEnum;
use Mom\Data\AbstractEnum;

/**
 * The kind of record the article is attached to.
 */
final class LinkableType extends AbstractEnum
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'linkable_type';
    }

    public static function forEloquentFactoryValue(): string
    {
        return ArticleLinkTargetEnum::Ticket->value;
    }

    public function toNullableEnum(): ?ArticleLinkTargetEnum
    {
        $value = $this->toValue();

        if ($value instanceof ArticleLinkTargetEnum) {
            return $value;
        }

        return is_string($value) ? ArticleLinkTargetEnum::tryFrom($value) : null;
    }

    public function toEnum(): ArticleLinkTargetEnum
    {
        return $this->toNullableEnum() ?? ArticleLinkTargetEnum::Ticket;
    }
}
