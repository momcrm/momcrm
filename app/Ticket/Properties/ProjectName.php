<?php

declare(strict_types=1);

namespace App\Ticket\Properties;

use Illuminate\Database\Eloquent\Model;
use Mom\Data\AbstractString;

/**
 * The project's name. Read from the eager-loaded relation when it is loaded; not a column.
 */
final class ProjectName extends AbstractString
{
    public static bool $hasDBColumn = false;

    public static function getName(): string
    {
        return 'project_name';
    }

    public static function fromEloquentModel(Model $model): static
    {
        $related = $model->relationLoaded('project') ? $model->getRelation('project') : null;

        return new self(value: $related instanceof Model ? $related->getAttribute('name') : null)->setData($model);
    }
}
