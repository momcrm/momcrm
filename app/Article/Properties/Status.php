<?php

declare(strict_types=1);

namespace App\Article\Properties;

use App\Article\ArticleStatusEnum;
use Mom\Data\AbstractEnum;

final class Status extends AbstractEnum
{
    public static function getName(): string
    {
        return 'status';
    }

    public static function forEloquentFactoryValue(): string
    {
        return ArticleStatusEnum::Draft->value;
    }

    public function toNullableEnum(): ?ArticleStatusEnum
    {
        $value = $this->toValue();

        if ($value instanceof ArticleStatusEnum) {
            return $value;
        }

        return is_string($value) ? ArticleStatusEnum::tryFrom($value) : null;
    }

    public function toEnum(): ArticleStatusEnum
    {
        return $this->toNullableEnum() ?? ArticleStatusEnum::Draft;
    }
}
