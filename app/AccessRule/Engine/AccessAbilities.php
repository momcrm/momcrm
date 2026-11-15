<?php

declare(strict_types=1);

namespace App\AccessRule\Engine;

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessResourceEnum;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Mom\Data\AbstractData;

/**
 * What someone may do to a record, for pages to show or hide their buttons.
 */
final class AccessAbilities
{
    /**
     * Keyed by camel-cased action: ['update' => true, 'changeVisibility' => false, ...].
     *
     * @param  AbstractData<covariant Model>|null  $record
     * @return array<string, bool>
     */
    public static function for(UserEloquentModel $user, AccessResourceEnum $resource, ?AbstractData $record = null): array
    {
        return collect($resource->actions())
            ->mapWithKeys(fn (AccessActionEnum $action): array => [
                Str::camel($action->value) => AccessEvaluator::allows($user, $resource, $action, $record),
            ])
            ->all();
    }
}
