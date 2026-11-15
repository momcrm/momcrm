<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\Choice\Actions\FindChoice;
use App\Choice\ChoiceListEnum;
use App\Customer\CustomerEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

it('saves changes and stamps the time when the status becomes closed', function (): void {
    travelTo('2026-11-14 15:00:00');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'number' => 5]);
    $urgent = FindChoice::byNameForTeam($user->current_team_id, ChoiceListEnum::TicketPriority, 'Urgent')->getId()->toString();

    actingAs($user)
        ->put(route('tickets.update', $ticket->id), ['customer_id' => $customer->id, 'title' => 'Renamed', 'priority_id' => $urgent, 'status' => 'closed', 'number' => 99])
        ->assertRedirectToRoute('tickets.show', $ticket->id);

    expect($ticket->fresh())
        ->title->toBe('Renamed')
        ->priority_id->toBe($urgent)
        ->status->toBe('closed')
        ->number->toBe(5)
        ->and($ticket->fresh()->closed_at?->toDateTimeString())->toBe('2026-11-14 15:00:00');
    expect(ActivityEloquentModel::query()->where('type', ActivityTypeEnum::TicketClosed->value)->exists())->toBeTrue();
});

it("returns not found for another team's ticket", function (): void {
    $other = TicketEloquentModel::factory()->create(['title' => 'Theirs']);

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->put(route('tickets.update', $other->id), ['customer_id' => $other->customer_id, 'title' => 'Mine', 'status' => 'new'])
        ->assertNotFound();

    expect($other->fresh()->title)->toBe('Theirs');
});
