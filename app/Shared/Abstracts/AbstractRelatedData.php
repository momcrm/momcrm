<?php

declare(strict_types=1);

namespace App\Shared\Abstracts;

use Illuminate\Database\Eloquent\Model;
use Mom\Data\AbstractData;
use Mom\Data\AbstractDataValue;

/**
 * A record the data object belongs to (a project's customer, a ticket's
 * assignee), as that record's own data object. Read lazily from the
 * eager-loaded relation; empty when the relation was not loaded. Not a column.
 *
 * @template TData of AbstractData
 */
abstract class AbstractRelatedData extends AbstractDataValue
{
    public static bool $hasDBColumn = false;

    /**
     * @return class-string<TData>
     */
    abstract public static function dataClass(): string;

    /**
     * The Eloquent relation to read; the property's name by default.
     */
    public static function getRelationshipName(): string
    {
        return static::getName();
    }

    public static function fromEloquentModel(Model $model): static
    {
        return static::fromClosure(function () use ($model): ?AbstractData {
            $relationship = static::getRelationshipName();
            $related = $model->relationLoaded($relationship) ? $model->getRelation($relationship) : null;

            return $related instanceof Model ? static::dataClass()::fromEloquentModel($related) : null;
        });
    }

    /**
     * @return TData|null
     */
    public function toNullableData(): ?AbstractData
    {
        $value = $this->toValue();
        $class = static::dataClass();

        return $value instanceof $class ? $value : null;
    }

    /**
     * @return TData
     */
    public function toData(): AbstractData
    {
        return $this->toNullableData() ?? static::dataClass()::new();
    }
}
