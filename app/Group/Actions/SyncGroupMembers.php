<?php

declare(strict_types=1);

namespace App\Group\Actions;

use App\Group\GroupEloquentModel;
use App\Group\GroupRoleEnum;

/**
 * Sets a group's members: the lead as its lead, everyone else as a member.
 */
final class SyncGroupMembers
{
    /**
     * @param  array<int, string>  $memberIds
     */
    public static function to(GroupEloquentModel $group, ?string $leadId, array $memberIds): void
    {
        $members = collect($memberIds)
            ->reject(fn (string $id): bool => $id === $leadId)
            ->mapWithKeys(fn (string $id): array => [$id => ['role' => GroupRoleEnum::Member->value]]);

        if ($leadId !== null) {
            $members->put($leadId, ['role' => GroupRoleEnum::Lead->value]);
        }

        $group->members()->sync($members->all());
    }
}
