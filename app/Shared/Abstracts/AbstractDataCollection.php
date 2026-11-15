<?php

declare(strict_types=1);

namespace App\Shared\Abstracts;

use App\Shared\Types;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * A collection of one domain's data objects, returned by its DTO's collect()
 * and by its finders. first() and findById() return an empty data object
 * rather than null, like the finders do. Like Eloquent's collection, anything
 * that turns the items into something else (map, mapWithKeys, pluck, keys)
 * returns a plain collection.
 *
 * @template TKey of array-key
 * @template TData of AbstractData
 *
 * @extends Collection<TKey, TData>
 */
abstract class AbstractDataCollection extends Collection
{
    /**
     * The data object the collection holds.
     *
     * @return class-string<TData>
     */
    abstract public static function dataClass(): string;

    /**
     * The first item, or the first one passing the callback; an empty data
     * object when there is none.
     *
     * @param  (callable(TData, TKey): bool)|null  $callback
     * @param  mixed  $default  ignored: an empty data object stands in for a missing item
     * @return TData
     */
    public function first(?callable $callback = null, $default = null): AbstractData
    {
        $item = parent::first($callback);
        $class = static::dataClass();

        return $item instanceof $class ? $item : $class::new();
    }

    /**
     * The item with this id, or an empty data object.
     *
     * @return TData
     */
    public function findById(mixed $id): AbstractData
    {
        if ($id instanceof AbstractData) {
            $id = $id->getPrimaryKey();
        }

        $id = $id instanceof AbstractValue ? $id->toValue() : $id;

        return $this->first(fn (AbstractData $item): bool => $item->getPrimaryKey()->toValue() === $id);
    }

    /**
     * The ids of the items, in order.
     *
     * @return list<string>
     */
    public function ids(): array
    {
        return array_values($this->map(fn (AbstractData $item): string => Types::string($item->getPrimaryKey()->toValue()))->all());
    }

    /**
     * @template TMapValue
     *
     * @param  callable(TData, TKey): TMapValue  $callback
     * @return Collection<TKey, TMapValue>
     */
    public function map(callable $callback): Collection
    {
        return $this->toBase()->map($callback);
    }

    /**
     * @template TMapWithKeysKey of array-key
     * @template TMapWithKeysValue
     *
     * @param  callable(TData, TKey): array<TMapWithKeysKey, TMapWithKeysValue>  $callback
     * @return Collection<TMapWithKeysKey, TMapWithKeysValue>
     */
    public function mapWithKeys(callable $callback): Collection
    {
        return $this->toBase()->mapWithKeys($callback);
    }

    /**
     * @param  string|int|array<array-key, string>|null  $value
     * @param  string|null  $key
     * @return Collection<array-key, mixed>
     */
    public function pluck($value, $key = null): Collection
    {
        return $this->toBase()->pluck($value, $key);
    }

    /**
     * @return Collection<int, TKey>
     */
    public function keys(): Collection
    {
        return $this->toBase()->keys();
    }
}
