<?php

declare(strict_types=1);

namespace App\AccessRule\Engine;

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessResourceEnum;
use App\User\UserEloquentModel;
use Illuminate\Contracts\Auth\Authenticatable;
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
     * Someone who isn't signed in may do nothing.
     *
     * @param  AbstractData<covariant Model>|null  $record
     * @return array<string, bool>
     */
    public static function for(?Authenticatable $user, AccessResourceEnum $resource, ?AbstractData $record = null): array
    {
        return collect($resource->actions())
            ->mapWithKeys(fn (AccessActionEnum $action): array => [
                Str::camel($action->value) => $user instanceof UserEloquentModel && AccessEvaluator::allows($user, $resource, $action, $record),
            ])
            ->all();
    }
}
