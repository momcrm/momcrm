<?php

declare(strict_types=1);

use App\Project\ProjectEloquentModel;
use App\Ticket\TicketChannel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('lets someone who can see the ticket listen', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    actingAs($user);

    expect(new TicketChannel()->join($user, $ticket->id))->toBeTrue();
});

it('keeps out someone from another team', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $theirs = TicketEloquentModel::factory()->create();
    actingAs($user);

    expect(new TicketChannel()->join($user, $theirs->id))->toBeFalse();
});

it("keeps out a teammate who isn't in the ticket's members-only project", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $outsider = messagingTeammate($owner);
    $project = ProjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'visibility' => 'restricted']);
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'project_id' => $project->id]);
    actingAs($outsider);

    expect(new TicketChannel()->join($outsider, $ticket->id))->toBeFalse();
});
