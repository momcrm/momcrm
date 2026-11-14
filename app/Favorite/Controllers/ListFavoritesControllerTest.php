<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Favorite\FavoriteEloquentModel;
use App\Project\ProjectEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('lists the projects, tickets and customers the person starred', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $project = ProjectEloquentModel::factory()->create([...$team, 'name' => 'Refit']);
    $ticket = TicketEloquentModel::factory()->create([...$team, 'title' => 'Printer']);
    $customer = CustomerEloquentModel::factory()->create([...$team, 'name' => 'Acme']);
    foreach ([$project, $ticket, $customer] as $record) {
        FavoriteEloquentModel::factory()->forFavoritable($record, $user)->create();
    }
    FavoriteEloquentModel::factory()->forFavoritable(ProjectEloquentModel::factory()->create($team))->create();

    actingAs($user)
        ->get(route('favorites.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('favorites')
            ->has('projects', 1)
            ->where('projects.0.name', 'Refit')
            ->where('projects.0.is_favorite', true)
            ->where('tickets.0.title', 'Printer')
            ->where('customers.0.name', 'Acme'),
        );
});
