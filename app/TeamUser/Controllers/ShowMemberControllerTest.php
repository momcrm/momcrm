<?php

declare(strict_types=1);

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Group\GroupEloquentModel;
use App\Project\ProjectEloquentModel;
use App\TeamUser\TeamUserEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

function teammate(UserEloquentModel $owner, array $attributes = [], string $role = 'member'): UserEloquentModel
{
    $user = UserEloquentModel::factory()->create(['current_team_id' => $owner->current_team_id, ...$attributes]);
    TeamUserEloquentModel::query()->create(['team_id' => $owner->current_team_id, 'user_id' => $user->id, 'role' => $role]);

    return $user;
}

it("shows a member's tickets, active projects, groups and their own activity", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $owner->current_team_id];
    $member = teammate($owner, ['name' => 'Ben', 'job_title' => 'Technician', 'timezone' => 'Australia/Perth']);
    GroupEloquentModel::factory()->create([...$team, 'name' => 'Support'])->members()->attach($member->id, ['role' => 'member']);
    ProjectEloquentModel::factory()->create([...$team, 'name' => 'Refit', 'status' => 'in_progress', 'lead_id' => $member->id]);
    ProjectEloquentModel::factory()->create([...$team, 'status' => 'completed', 'lead_id' => $member->id]);
    ProjectEloquentModel::factory()->create($team);
    TicketEloquentModel::factory()->create([...$team, 'assignee_id' => $member->id, 'title' => 'Late', 'due_on' => now()->subDay()->toDateString()]);
    TicketEloquentModel::factory()->create([...$team, 'assignee_id' => $member->id, 'title' => 'Later', 'due_on' => now()->addDay()->toDateString()]);
    TicketEloquentModel::factory()->closed()->create([...$team, 'assignee_id' => $member->id, 'title' => 'Done']);
    TicketEloquentModel::factory()->create([...$team, 'assignee_id' => $owner->id]);
    RecordActivity::for(ActivityTypeEnum::TicketOpened, $member->id, $owner->current_team_id);
    RecordActivity::for(ActivityTypeEnum::TicketOpened, $owner->id, $owner->current_team_id);

    actingAs($owner)
        ->get(route('members.show', $member->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('members/show')
            ->where('member.name', 'Ben')
            ->where('member.job_title', 'Technician')
            ->where('member.timezone', 'Australia/Perth')
            ->where('member.role', 'member')
            ->where('member.can_remove', true)
            ->where('member.groups.0.name', 'Support')
            ->whereNot('member.last_active_at', null)
            ->where('stats', ['active_projects' => 1, 'open_tickets' => 2, 'closed_tickets' => 1, 'overdue_tickets' => 1])
            ->where('openTickets', fn ($tickets): bool => collect($tickets)->pluck('title')->all() === ['Late', 'Later'])
            ->where('closedTickets.0.title', 'Done')
            ->has('projects', 1)
            ->where('projects.0.name', 'Refit')
            ->where('projects.0.is_lead', true)
            ->has('activity', 1)
            ->where('activity.0.actor', 'Ben')
            ->where('canChangeRoles', true),
        );
});

it("leaves out tickets in a members-only project the viewer isn't in", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $owner->current_team_id];
    $viewer = teammate($owner);
    $member = teammate($owner);
    $private = ProjectEloquentModel::factory()->create([...$team, 'visibility' => 'restricted']);
    $private->members()->attach($member->id);
    TicketEloquentModel::factory()->create([...$team, 'assignee_id' => $member->id, 'project_id' => $private->id]);

    actingAs($viewer)
        ->get(route('members.show', $member->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.open_tickets', 0)
            ->has('openTickets', 0)
            ->has('projects', 0)
            ->where('member.can_remove', false)
            ->where('canChangeRoles', false),
        );
});

it('returns not found for someone outside the team', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->get(route('members.show', UserEloquentModel::factory()->withPersonalTeam()->create()->id))
        ->assertNotFound();
});

it('counts a sign-in as being active, and has no last activity for someone who never signed in', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $signedIn = teammate($owner);
    $neverSignedIn = teammate($owner);
    RecordActivity::for(ActivityTypeEnum::SignedIn, $signedIn->id);

    actingAs($owner)
        ->get(route('members.show', $signedIn->id))
        ->assertInertia(fn (Assert $page) => $page->whereNot('member.last_active_at', null));

    actingAs($owner)
        ->get(route('members.show', $neverSignedIn->id))
        ->assertInertia(fn (Assert $page) => $page->where('member.last_active_at', null));
});
