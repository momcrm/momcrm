<?php

declare(strict_types=1);

namespace App\Article\Properties;

use App\Article\ArticleClassificationEnum;
use Mom\Data\AbstractEnum;

final class Classification extends AbstractEnum
{
    public static function getName(): string
    {
        return 'classification';
    }

    public static function forEloquentFactoryValue(): string
    {
        return ArticleClassificationEnum::Internal->value;
    }

    public function toNullableEnum(): ?ArticleClassificationEnum
    {
        $value = $this->toValue();

        if ($value instanceof ArticleClassificationEnum) {
            return $value;
        }

        return is_string($value) ? ArticleClassificationEnum::tryFrom($value) : null;
    }

    public function toEnum(): ArticleClassificationEnum
    {
        return $this->toNullableEnum() ?? ArticleClassificationEnum::Internal;
    }
}
