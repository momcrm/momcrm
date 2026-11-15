<?php

declare(strict_types=1);

namespace App\Attachment\Properties;

use Illuminate\Database\Eloquent\Model;
use Mom\Data\AbstractString;

/**
 * The name of who uploaded the file. Read from the eager-loaded relation when it is loaded; not a column.
 */
final class UploaderName extends AbstractString
{
    public static bool $hasDBColumn = false;

    public static function getName(): string
    {
        return 'uploader_name';
    }

    public static function fromEloquentModel(Model $model): static
    {
        $related = $model->relationLoaded('uploader') ? $model->getRelation('uploader') : null;

        return new self(value: $related instanceof Model ? $related->getAttribute('name') : null)->setData($model);
    }
}
