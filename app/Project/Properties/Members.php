<?php

declare(strict_types=1);

namespace App\Project\Properties;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Mom\Data\AbstractCollection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * The members, as id and name. Read from the eager-loaded relation when it is loaded; not a column.
 */
final class Members extends AbstractCollection
{
    public static bool $hasDBColumn = false;

    public static function getName(): string
    {
        return 'members';
    }

    public static function fromEloquentModel(Model $model): static
    {
        $related = $model->relationLoaded('members') ? $model->getRelation('members') : null;

        return new self(value: $related instanceof Collection
            ? $related->map(fn (Model $item): array => ['id' => $item->getAttribute('id'), 'name' => $item->getAttribute('name')])->values()->all()
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
