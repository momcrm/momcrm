<?php

declare(strict_types=1);

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Group\GroupEloquentModel;
use App\Project\ProjectEloquentModel;
use App\TeamInvitation\TeamInvitationEloquentModel;
use App\TeamUser\TeamUserEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('lists members with their groups, open work and last activity', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['name' => 'Ada']);
    $teamId = $owner->current_team_id;
    $member = UserEloquentModel::factory()->create(['name' => 'Ben']);
    TeamUserEloquentModel::query()->create(['team_id' => $teamId, 'user_id' => $member->id, 'role' => 'member']);
    $group = GroupEloquentModel::factory()->create(['team_id' => $teamId, 'name' => 'Support']);
    $group->members()->attach($member->id, ['role' => 'lead']);
    ProjectEloquentModel::factory()->create(['team_id' => $teamId])->members()->attach($member->id);
    TicketEloquentModel::factory()->count(2)->create(['team_id' => $teamId, 'assignee_id' => $member->id]);
    RecordActivity::for(ActivityTypeEnum::TicketOpened, $member->id, $teamId);

    actingAs($owner)
        ->get(route('members'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('members')
            ->has('members', 2)
            ->where('members.1.name', 'Ben')
            ->where('members.1.groups.0.name', 'Support')
            ->where('members.1.active_projects', 1)
            ->where('members.1.open_tickets', 2)
            ->whereNot('members.1.last_active_at', null)
            ->where('members.0.role', 'owner')
            ->where('members.0.is_you', true)
            ->where('members.0.can_remove', false)
            ->where('members.1.is_you', false)
            ->where('members.1.can_remove', true)
            ->where('teamName', $owner->currentTeam->name)
            ->where('groups.0.members_count', 1)
            ->where('groups.0.members.0.name', 'Ben')
            ->where('canChangeRoles', true)
            ->where('canInvite', true),
        );
});

it('does not let plain members change roles or see invitations', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->create(['current_team_id' => $owner->current_team_id]);
    TeamUserEloquentModel::query()->create(['team_id' => $owner->current_team_id, 'user_id' => $member->id, 'role' => 'member']);

    actingAs($member)
        ->get(route('members'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('canChangeRoles', false)
            ->where('canInvite', false)
            ->where('invitations', [])
            ->where('members.0.can_remove', false)
            ->where('members.1.can_remove', false),
        );
});

it('lists pending invitations with the group they will join', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $group = GroupEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    TeamInvitationEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'email' => 'sam@acme.test', 'group_id' => $group->id]);

    actingAs($owner)
        ->get(route('members'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('invitations.0.email', 'sam@acme.test')
            ->where('invitations.0.group_id', $group->id),
        );
});
