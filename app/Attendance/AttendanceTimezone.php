<?php

declare(strict_types=1);

namespace App\Attendance;

use App\TeamUser\Actions\FindTeamMembers;
use App\User\Actions\FindUser;
use App\User\User;
use Illuminate\Support\Collection;

/**
 * The timezone a member's working days are counted in: their own, or the
 * app's when they have not set one.
 */
final class AttendanceTimezone
{
    public static function forUserId(string $userId): string
    {
        return self::forUser(FindUser::byId($userId));
    }

    public static function forUser(User $user): string
    {
        return self::orDefault($user->getTimezone()->toNullableString());
    }

    /**
     * The member's own timezone, or the app's when they have none.
     */
    public static function orDefault(?string $timezone): string
    {
        return $timezone !== null && $timezone !== '' ? $timezone : config()->string('app.timezone');
    }

    /**
     * The team's members with the timezone their days are counted in, by name.
     *
     * @return Collection<int, array{id: string, name: string, timezone: string}>
     */
    public static function membersOfTeam(string $teamId): Collection
    {
        return FindTeamMembers::withTimezonesForTeam($teamId)
            ->map(fn (array $member): array => [...$member, 'timezone' => self::orDefault($member['timezone'])]);
    }
}
