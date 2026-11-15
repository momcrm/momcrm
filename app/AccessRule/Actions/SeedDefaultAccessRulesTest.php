<?php

declare(strict_types=1);

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessResourceEnum;
use App\AccessRule\AccessRuleEloquentModel;
use App\AccessRule\Engine\AccessEvaluator;
use App\Team\Actions\CreateTeam;
use App\Team\Team;
use App\TeamUser\Actions\CreateTeamUser;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

it('gives every new team the default rules', function (): void {
    $owner = UserEloquentModel::factory()->create();
    $team = CreateTeam::fromTeam(Team::new()->setName('Acme')->setOwnerId($owner->id));

    expect(AccessRuleEloquentModel::query()->withoutGlobalScopes()->where('team_id', $team->getId()->toString())->orderBy('effect')->pluck('name')->all())
        ->toBe(['Everyone can work on everything', 'Only owners and admins delete, archive and manage access']);
});

it('lets members create and edit but leaves deleting, archiving and managing access to owners and admins', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = (string) $owner->current_team_id;
    $member = UserEloquentModel::factory()->create(['current_team_id' => $teamId]);
    $admin = UserEloquentModel::factory()->create(['current_team_id' => $teamId]);
    CreateTeamUser::fromIds(teamId: $teamId, userId: $member->id, role: TeamRoleEnum::Member);
    CreateTeamUser::fromIds(teamId: $teamId, userId: $admin->id, role: TeamRoleEnum::Admin);

    foreach (AccessResourceEnum::cases() as $resource) {
        foreach ($resource->actions() as $action) {
            $restricted = in_array($action, [AccessActionEnum::Delete, AccessActionEnum::Archive, AccessActionEnum::Restore, AccessActionEnum::ChangeVisibility, AccessActionEnum::ManageMembers], true)
                && ! in_array($resource, [AccessResourceEnum::Comment, AccessResourceEnum::Attachment], true);

            expect(AccessEvaluator::allows($member, $resource, $action))->toBe(! $restricted, "member: {$action->value} {$resource->value}")
                ->and(AccessEvaluator::allows($admin, $resource, $action))->toBeTrue("admin: {$action->value} {$resource->value}");
        }
    }
});
