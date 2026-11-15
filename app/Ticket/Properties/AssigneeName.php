<?php

declare(strict_types=1);

namespace App\Ticket\Properties;

use Illuminate\Database\Eloquent\Model;
use Mom\Data\AbstractString;

/**
 * The assignee's name. Read from the eager-loaded relation when it is loaded; not a column.
 */
final class AssigneeName extends AbstractString
{
    public static bool $hasDBColumn = false;

    public static function getName(): string
    {
        return 'assignee_name';
    }

    public static function fromEloquentModel(Model $model): static
    {
        $related = $model->relationLoaded('assignee') ? $model->getRelation('assignee') : null;

        return new self(value: $related instanceof Model ? $related->getAttribute('name') : null)->setData($model);
    }
}
