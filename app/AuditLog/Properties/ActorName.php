<?php

declare(strict_types=1);

namespace App\AuditLog\Properties;

use Illuminate\Database\Eloquent\Model;
use Mom\Data\AbstractString;

/**
 * The name of who acted. Read from the eager-loaded relation when it is loaded; not a column.
 */
final class ActorName extends AbstractString
{
    public static bool $hasDBColumn = false;

    public static function getName(): string
    {
        return 'actor_name';
    }

    public static function fromEloquentModel(Model $model): static
    {
        $related = $model->relationLoaded('actor') ? $model->getRelation('actor') : null;

        return new self(value: $related instanceof Model ? $related->getAttribute('name') : null)->setData($model);
    }
}
