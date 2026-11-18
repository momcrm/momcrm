<?php

declare(strict_types=1);

namespace App\Comment\Properties;

use App\Comment\CommentVisibilityEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mom\Data\AbstractData;
use Mom\Data\AbstractEnum;
use Mom\Data\AbstractValue;

final class Visibility extends AbstractEnum
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'visibility';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['sometimes', Rule::enum(CommentVisibilityEnum::class)];
    }

    public static function forEloquentFactoryValue(): string
    {
        return CommentVisibilityEnum::Internal->value;
    }

    /**
     * A comment saved without saying is an internal note.
     *
     * @param  AbstractData<Model>  $data
     */
    public static function forDatabaseCreateValue(AbstractValue $value, AbstractData $data): string
    {
        return $value instanceof self ? $value->toEnum()->value : CommentVisibilityEnum::Internal->value;
    }

    public function toNullableEnum(): ?CommentVisibilityEnum
    {
        $value = $this->toValue();

        if ($value instanceof CommentVisibilityEnum) {
            return $value;
        }

        return is_string($value) ? CommentVisibilityEnum::tryFrom($value) : null;
    }

    /**
     * An unreadable visibility counts as internal, so nothing is shown or
     * sent to a customer by mistake.
     */
    public function toEnum(): CommentVisibilityEnum
    {
        return $this->toNullableEnum() ?? CommentVisibilityEnum::Internal;
    }
}
