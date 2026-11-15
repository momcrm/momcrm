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
use App\Ticket\Ticket;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

it('gives every new team the default rules', function (): void {
    $owner = UserEloquentModel::factory()->create();
    $team = CreateTeam::fromTeam(Team::new()->setName('Acme')->setOwnerId($owner->id));

    expect(AccessRuleEloquentModel::query()->withoutGlobalScopes()->where('team_id', $team->getId()->toString())->orderBy('name')->pluck('name')->all())
        ->toBe(['Everyone can create and edit', 'Everyone can delete comments and files', 'Owners and admins can do everything']);
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

it('lets an admin grant members more with an allow rule', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = (string) $owner->current_team_id;
    $member = UserEloquentModel::factory()->create(['current_team_id' => $teamId]);
    CreateTeamUser::fromIds(teamId: $teamId, userId: $member->id, role: TeamRoleEnum::Member);
    AccessRuleEloquentModel::factory()->create([
        'team_id' => $teamId,
        'resources' => ['ticket'],
        'actions' => ['archive'],
        'conditions' => [['attribute' => 'ticket.subject_is_assignee', 'operator' => 'is_true']],
    ]);
    $theirs = Ticket::fromEloquentModel(TicketEloquentModel::factory()->create(['team_id' => $teamId, 'assignee_id' => $member->id]));
    $others = Ticket::fromEloquentModel(TicketEloquentModel::factory()->create(['team_id' => $teamId]));

    expect(AccessEvaluator::allows($member, AccessResourceEnum::Ticket, AccessActionEnum::Archive, $theirs))->toBeTrue()
        ->and(AccessEvaluator::allows($member, AccessResourceEnum::Ticket, AccessActionEnum::Archive, $others))->toBeFalse()
        ->and(AccessEvaluator::allows($member, AccessResourceEnum::Ticket, AccessActionEnum::Delete, $theirs))->toBeFalse();
});
