<?php

declare(strict_types=1);

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomObject\CustomObjectEloquentModel;
use App\Group\GroupEloquentModel;
use App\LeaveRequest\LeaveRequestEloquentModel;
use App\Project\ProjectEloquentModel;
use App\Record\RecordEloquentModel;
use App\Shift\ShiftEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\TeamUser\TeamUserEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\Timesheet\TimesheetEloquentModel;
use App\User\UserEloquentModel;
use App\WorkSchedule\WorkScheduleEloquentModel;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

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

it('lists what links to the member', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $team = ['team_id' => $owner->current_team_id];
    $vehicles = CustomObjectEloquentModel::factory()->create([...$team, 'name_plural' => 'Vehicles']);
    $field = CustomFieldEloquentModel::factory()->forObject($vehicles)->linkingTo(CustomFieldRelationEnum::Member)->create(['name' => 'Linked member']);
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $van = RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Van', 'custom_fields' => [$field->id => $member->id]]);

    Pest\Laravel\actingAs($owner)
        ->get(route('members.show', $member->id))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('linkedRecords', 1)
            ->where('linkedRecords.0.source', 'Vehicles')
            ->where('linkedRecords.0.field', 'Linked member')
            ->where('linkedRecords.0.records.0', ['id' => $van->id, 'title' => 'Van', 'url' => route('records.show', [$vehicles->id, $van->id])]),
        );
});

it('shows owners and the member themselves their attendance, but not other members', function (): void {
    travelTo('2026-11-25 12:00:00');
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['timezone' => 'UTC']);
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create(['timezone' => 'UTC']);
    $other = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    WorkScheduleEloquentModel::factory()->forTeam($owner->current_team_id)->teamDefault()->weekdays('09:00', '17:00')->create();
    ShiftEloquentModel::factory()->forUser($member)->between(CarbonImmutable::parse('2026-11-24 09:00'), 8)->create();
    LeaveRequestEloquentModel::factory()->forUser($member)->between('2026-12-21', '2026-12-25')->approved()->create();
    TimesheetEloquentModel::factory()->forUser($member)->period('2026-11-16')->approved()->create();

    actingAs($owner)
        ->get(route('members.show', $member->id))
        ->assertInertia(fn (Assert $page) => $page
            ->where('attendance.week_worked_seconds', 8 * 3600)
            ->where('attendance.week_expected_seconds', 5 * 8 * 3600)
            ->where('attendance.leave_days_this_year', 5)
            ->where('attendance.last_timesheet', ['starts_on' => '2026-11-16', 'ends_on' => '2026-11-22', 'status' => 'approved']));

    actingAs($member)->get(route('members.show', $member->id))->assertInertia(fn (Assert $page) => $page->has('attendance'));
    actingAs($other)->get(route('members.show', $member->id))->assertInertia(fn (Assert $page) => $page->where('attendance', null));
});
