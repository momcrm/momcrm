<?php

declare(strict_types=1);

use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("lists the person's open tickets soonest due first, plus recently closed ones", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $mine = ['team_id' => $user->current_team_id, 'assignee_id' => $user->id];
    TicketEloquentModel::factory()->create([...$mine, 'title' => 'Later', 'due_on' => '2026-12-20']);
    TicketEloquentModel::factory()->create([...$mine, 'title' => 'Sooner', 'due_on' => '2026-12-01']);
    TicketEloquentModel::factory()->create([...$mine, 'title' => 'Undated']);
    TicketEloquentModel::factory()->create([...$mine, 'title' => 'Just closed', 'status_id' => ticketStatusId($user->current_team_id, 'Closed'), 'closed_at' => now()->subDay()]);
    TicketEloquentModel::factory()->create([...$mine, 'title' => 'Long closed', 'status_id' => ticketStatusId($user->current_team_id, 'Closed'), 'closed_at' => now()->subMonth()]);
    TicketEloquentModel::factory()->create([...$mine, 'title' => 'Archived'])->update(['archived_at' => now()]);
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'title' => 'Someone else']);

    actingAs($user)
        ->get(route('tickets.mine'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tickets/mine')
            ->where('tickets', function ($tickets): bool {
                $titles = collect($tickets)->pluck('title');

                return $titles->take(2)->all() === ['Sooner', 'Later']
                    && $titles->slice(2)->sort()->values()->all() === ['Just closed', 'Undated'];
            }),
        );
});
