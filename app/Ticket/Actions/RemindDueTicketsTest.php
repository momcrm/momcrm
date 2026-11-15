<?php

declare(strict_types=1);

use App\Ticket\Actions\RemindDueTickets;
use App\Ticket\Notifications\TicketDueSoonNotification;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

it('reminds assignees of open tickets due tomorrow', function (): void {
    Notification::fake();
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $mine = ['team_id' => $user->current_team_id, 'assignee_id' => $user->id];
    $due = TicketEloquentModel::factory()->create([...$mine, 'due_on' => '2026-12-02']);
    TicketEloquentModel::factory()->create([...$mine, 'due_on' => '2026-12-03']);
    TicketEloquentModel::factory()->closed()->create([...$mine, 'due_on' => '2026-12-02']);
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'due_on' => '2026-12-02']);

    expect(RemindDueTickets::run(CarbonImmutable::parse('2026-12-01')))->toBe(1);

    Notification::assertSentTo($user, TicketDueSoonNotification::class, fn (TicketDueSoonNotification $notification): bool => $notification->ticket->is($due));
    Notification::assertCount(1);
});
