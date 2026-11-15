<?php

declare(strict_types=1);

use App\AccessRule\AccessRuleEloquentModel;
use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\Project\ProjectEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('archives a ticket, taking it out of the list', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'number' => 4]);

    actingAs($user)
        ->post(route('tickets.archive', $ticket->id))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Ticket #4 archived.']);

    expect($ticket->fresh()->archived_at)->not->toBeNull()
        ->and(ActivityEloquentModel::query()->where('type', ActivityTypeEnum::TicketArchived->value)->sole()->subject_id)->toBe($ticket->id);

    actingAs($user)->get(route('tickets.index'))
        ->assertInertia(fn ($page) => $page->has('tickets.data', 0));
});

it("returns not found for another team's ticket", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('tickets.archive', TicketEloquentModel::factory()->create()->id))
        ->assertNotFound();
});

it('lets members archive only what an access rule allows', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $led = ProjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'lead_id' => $member->id]);
    $inLed = TicketEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'project_id' => $led->id]);
    $elsewhere = TicketEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($member)->post(route('tickets.archive', $inLed->id))->assertForbidden();

    AccessRuleEloquentModel::factory()->create([
        'team_id' => $owner->current_team_id,
        'resources' => ['ticket'],
        'actions' => ['archive'],
        'conditions' => [['attribute' => 'ticket.subject_is_project_lead', 'operator' => 'is_true']],
    ]);

    actingAs($member)->post(route('tickets.archive', $inLed->id))->assertRedirect();
    actingAs($member)->post(route('tickets.archive', $elsewhere->id))->assertForbidden();

    expect($inLed->fresh()->archived_at)->not->toBeNull()
        ->and($elsewhere->fresh()->archived_at)->toBeNull();
});
