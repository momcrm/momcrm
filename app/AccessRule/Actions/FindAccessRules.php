<?php

declare(strict_types=1);

namespace App\AccessRule\Actions;

use App\AccessRule\AccessAudienceEnum;
use App\AccessRule\AccessRule;
use App\AccessRule\AccessRuleCollection;
use App\AccessRule\AccessRuleEloquentModel;
use App\AccessRule\Properties\Audience;
use App\AccessRule\Properties\CreatedAt;
use App\AccessRule\Properties\Id;
use App\AccessRule\Properties\IsEnabled;
use App\AccessRule\Properties\TeamId;
use App\Shared\RequestMemo;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class FindAccessRules
{
    /**
     * The team's enabled rules for one audience, loaded once per request.
     */
    public static function enabledForTeam(string $teamId, AccessAudienceEnum $audience = AccessAudienceEnum::Staff): AccessRuleCollection
    {
        return RequestMemo::remember(self::memoKey($teamId).".{$audience->value}", fn (): Collection => AccessRule::collect(
            self::query($teamId)
                ->where(IsEnabled::getDatabaseTableColumnName(), true)
                ->where(Audience::getDatabaseTableColumnName(), $audience->value)
                ->get()
                ->all(),
        ));
    }

    /**
     * Every rule in the team, oldest first, for the settings page.
     */
    public static function allForTeam(string $teamId): AccessRuleCollection
    {
        return AccessRule::collect(self::query($teamId)->get()->all());
    }

    public static function byIdForTeam(?string $id, ?string $teamId): AccessRule
    {
        if ($id === null || $teamId === null) {
            return AccessRule::new();
        }

        return AccessRule::fromEloquentModel(self::query($teamId)->where(Id::getDatabaseTableColumnName(), $id)->first());
    }

    /**
     * Drops the team's remembered rules after they change.
     */
    public static function forgetForTeam(string $teamId): void
    {
        RequestMemo::forget(self::memoKey($teamId));
    }

    private static function memoKey(string $teamId): string
    {
        return "access-rules.{$teamId}";
    }

    /**
     * @return Builder<AccessRuleEloquentModel>
     */
    private static function query(string $teamId): Builder
    {
        return AccessRuleEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->orderBy(CreatedAt::getDatabaseTableColumnName())
            ->orderBy(Id::getDatabaseTableColumnName());
    }
}
