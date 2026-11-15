<?php

declare(strict_types=1);

namespace App\Shared\Abstracts;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Mom\Data\AbstractCollection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * Records the data object has many of (a project's members, a ticket's
 * labels), as their domain's typed collection. Read lazily from the
 * eager-loaded relation; empty when the relation was not loaded. Not a column.
 * Each property narrows toDataCollection() to its domain's collection.
 */
abstract class AbstractRelatedDataCollection extends AbstractCollection
{
    public static bool $hasDBColumn = false;

    /**
     * @return class-string<AbstractDataCollection<int, covariant AbstractData<covariant Model>>>
     */
    abstract public static function collectionClass(): string;

    /**
     * The Eloquent relation to read; the property's name by default.
     */
    public static function getRelationshipName(): string
    {
        return static::getName();
    }

    public static function fromEloquentModel(Model $model): static
    {
        return new static(function () use ($model) {
            $relationship = static::getRelationshipName();
            $related = $model->relationLoaded($relationship) ? $model->getRelation($relationship) : null;

            if (! $related instanceof EloquentCollection) {
                return null;
            }

            $collection = static::collectionClass();
            $dataClass = $collection::dataClass();

            return new $collection($related->map(fn (Model $item): AbstractData => $dataClass::from($item))->all());
        });
    }

    /**
     * @return AbstractDataCollection<int, covariant AbstractData<covariant Model>>
     */
    public function toDataCollection(): AbstractDataCollection
    {
        $value = $this->toValue();
        $collection = static::collectionClass();

        return $value instanceof $collection ? $value : new $collection;
    }

    /**
     * @param  AbstractData<Model>  $data
     * @return array<int, mixed>
     */
    public static function forDatabaseCreateValue(AbstractValue $value, AbstractData $data): array
    {
        return [];
    }
}
