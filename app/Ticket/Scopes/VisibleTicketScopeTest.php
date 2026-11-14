<?php

declare(strict_types=1);

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Project\ProjectEloquentModel;
use App\TeamUser\TeamUserEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/**
 * A team with an outsider (a team member who is not in the restricted
 * project), a secret ticket in that project and an ordinary one.
 *
 * @return array{outsider: UserEloquentModel, member: UserEloquentModel, project: ProjectEloquentModel, secret: TicketEloquentModel, open: TicketEloquentModel}
 */
function restrictedProjectSetup(): array
{
    $member = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $member->current_team_id;
    $outsider = UserEloquentModel::factory()->create(['current_team_id' => $teamId]);
    TeamUserEloquentModel::query()->create(['team_id' => $teamId, 'user_id' => $outsider->id, 'role' => 'member']);
    $project = ProjectEloquentModel::factory()->create(['team_id' => $teamId, 'visibility' => 'restricted', 'name' => 'Secret project']);
    $project->members()->attach($member->id);

    return [
        'outsider' => $outsider,
        'member' => $member,
        'project' => $project,
        'secret' => TicketEloquentModel::factory()->create(['team_id' => $teamId, 'project_id' => $project->id, 'title' => 'Secret ticket', 'due_on' => now()->addDay()->toDateString()]),
        'open' => TicketEloquentModel::factory()->create(['team_id' => $teamId, 'title' => 'Ordinary ticket', 'due_on' => now()->addDay()->toDateString()]),
    ];
}

it('keeps tickets in members-only projects out of lists, search, calendar and counts', function (): void {
    ['outsider' => $outsider, 'secret' => $secret] = restrictedProjectSetup();
    $titles = fn ($tickets): array => collect($tickets)->pluck('title')->all();

    actingAs($outsider)
        ->get(route('tickets.index', ['status' => 'all']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('tickets.data', fn ($tickets): bool => $titles($tickets) === ['Ordinary ticket'])
            ->where('sidebarCounts.openTickets', 1));

    actingAs($outsider)
        ->get(route('calendar'))
        ->assertInertia(fn (Assert $page) => $page->where('tickets', fn ($tickets): bool => $titles($tickets) === ['Ordinary ticket']));

    actingAs($outsider)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('deadlines', fn ($tickets): bool => $titles($tickets) === ['Ordinary ticket'])
            ->where('stats.open_tickets', 1));

    actingAs($outsider)
        ->getJson(route('search', ['q' => 'ticket']))
        ->assertJsonCount(1, 'tickets')
        ->assertJsonPath('tickets.0.title', 'Ordinary ticket');

    actingAs($outsider)
        ->get(route('tickets.index', ['ticket' => $secret->id]))
        ->assertInertia(fn (Assert $page) => $page->where('drawer', null));
});

it('returns not found when opening or changing a hidden ticket directly', function (): void {
    ['outsider' => $outsider, 'secret' => $secret] = restrictedProjectSetup();

    actingAs($outsider)->get(route('tickets.show', $secret->id))->assertNotFound();
    actingAs($outsider)->patch(route('tickets.patch', $secret->id), ['priority' => 'low'])->assertNotFound();
    actingAs($outsider)->post(route('comments.store', $secret->id), ['body' => 'Hi'])->assertNotFound();
});

it('shows the tickets to project members and to the assignee', function (): void {
    ['outsider' => $outsider, 'member' => $member, 'secret' => $secret] = restrictedProjectSetup();

    actingAs($member)->get(route('tickets.show', $secret->id))->assertOk();

    $secret->update(['assignee_id' => $outsider->id]);

    actingAs($outsider)->get(route('tickets.show', $secret->id))->assertOk();
    actingAs($outsider)
        ->get(route('tickets.mine'))
        ->assertInertia(fn (Assert $page) => $page->where('tickets.0.title', 'Secret ticket'));
});

it('leaves activity about hidden projects and their tickets out of the feeds', function (): void {
    ['outsider' => $outsider, 'member' => $member, 'project' => $project, 'secret' => $secret, 'open' => $open] = restrictedProjectSetup();
    $teamId = $member->current_team_id;
    RecordActivity::for(ActivityTypeEnum::ProjectCreated, $member->id, $teamId, $project, ['name' => 'Secret project']);
    RecordActivity::for(ActivityTypeEnum::TicketOpened, $member->id, $teamId, $secret, ['number' => $secret->number]);
    RecordActivity::for(ActivityTypeEnum::TicketOpened, $member->id, $teamId, $open, ['number' => $open->number]);

    actingAs($outsider)
        ->get(route('activity'))
        ->assertInertia(fn (Assert $page) => $page->where('activities.total', 1));
    actingAs($outsider)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->has('activity', 1));
    actingAs($member)
        ->get(route('activity'))
        ->assertInertia(fn (Assert $page) => $page->where('activities.total', 3));
});
