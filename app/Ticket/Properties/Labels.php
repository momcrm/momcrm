<?php

declare(strict_types=1);

namespace App\Ticket\Properties;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Mom\Data\AbstractCollection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * The labels, as id, name and colour. Read from the eager-loaded relation when it is loaded; not a column.
 */
final class Labels extends AbstractCollection
{
    public static bool $hasDBColumn = false;

    public static function getName(): string
    {
        return 'labels';
    }

    public static function fromEloquentModel(Model $model): static
    {
        $related = $model->relationLoaded('labels') ? $model->getRelation('labels') : null;

        return new self(value: $related instanceof Collection
            ? $related->map(fn (Model $item): array => ['id' => $item->getAttribute('id'), 'name' => $item->getAttribute('name'), 'color' => $item->getAttribute('color')])->values()->all()
            : [])->setData($model);
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
