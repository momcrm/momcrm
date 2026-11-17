<?php

declare(strict_types=1);

use App\Comment\Notifications\MentionedInRecordCommentNotification;
use App\CustomObject\CustomObjectEloquentModel;
use App\Record\RecordEloquentModel;
use App\Ticket\Notifications\TicketDueSoonNotification;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('lists every notification with a link to its ticket', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $user->notify(new TicketDueSoonNotification($ticket));

    actingAs($user)
        ->get(route('notifications.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('notifications')
            ->where('notifications.0.message', 'due tomorrow')
            ->where('notifications.0.url', route('tickets.index', ['ticket' => $ticket->id])),
        );
});

it("links a mention on a custom object's record to the record's page", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name_plural' => 'Vehicles']);
    $van = RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Van']);
    $user->notify(new MentionedInRecordCommentNotification($van, 'Vehicles', null, 'Service is due'));

    actingAs($user)
        ->get(route('notifications.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('notifications.0.subject_type', 'record')
            ->where('notifications.0.title', 'Van')
            ->where('notifications.0.project_name', 'Vehicles')
            ->where('notifications.0.url', route('records.show', [$vehicles->id, $van->id])),
        );
});
