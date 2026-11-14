<?php

declare(strict_types=1);

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Team\TeamEloquentModel;
use App\TeamUser\Actions\CreateTeamUser;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows the owner their teams activity and audit trail', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $otherTeam = TeamEloquentModel::factory()->create();
    RecordActivity::for(type: ActivityTypeEnum::TeamRenamed, userId: $owner->id, teamId: $owner->current_team_id, details: ['from' => 'A', 'to' => 'B']);
    RecordActivity::for(type: ActivityTypeEnum::TeamRenamed, userId: $otherTeam->owner_id, teamId: $otherTeam->id);

    actingAs($owner)
        ->get(route('team-activity.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/team-activity')
            ->where('team.id', $owner->current_team_id)
            ->has('activities.data', 1)
            ->where('activities.data.0.details', ['from' => 'A', 'to' => 'B'])
            ->where('auditLogs.data', fn ($rows): bool => collect($rows)->isNotEmpty()
                && collect($rows)->every(fn (array $row): bool => in_array($row['subject'], ['team', 'team_user'], true))),
        );
});

it('forbids members who do not own the team', function (): void {
    $team = TeamEloquentModel::factory()->create();
    $member = UserEloquentModel::factory()->create();
    CreateTeamUser::fromIds(teamId: $team->id, userId: $member->id);
    $member->forceFill(['current_team_id' => $team->id])->save();

    actingAs($member)
        ->get(route('team-activity.index'))
        ->assertForbidden();
});
