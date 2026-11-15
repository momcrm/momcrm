<?php

declare(strict_types=1);

namespace App\Project\Properties;

use Illuminate\Database\Eloquent\Model;
use Mom\Data\AbstractString;

/**
 * The customer's name. Read from the eager-loaded relation when it is loaded; not a column.
 */
final class CustomerName extends AbstractString
{
    public static bool $hasDBColumn = false;

    public static function getName(): string
    {
        return 'customer_name';
    }

    public static function fromEloquentModel(Model $model): static
    {
        $related = $model->relationLoaded('customer') ? $model->getRelation('customer') : null;

        return new self(value: $related instanceof Model ? $related->getAttribute('name') : null)->setData($model);
    }
}
