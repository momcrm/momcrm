<?php

declare(strict_types=1);

use App\Comment\Notifications\TicketCommentedNotification;
use App\Mailbox\MailboxEloquentModel;
use App\Project\ProjectEloquentModel;
use App\Shift\Notifications\ShiftStillOpenNotification;
use App\Shift\ShiftEloquentModel;
use App\Ticket\Notifications\TicketAssignedNotification;
use App\Ticket\Notifications\TicketDueSoonNotification;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Mail\Markdown;

it('emails mentions and assignments until the person chooses', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    expect(new TicketAssignedNotification($ticket, null)->via($user))->toBe(['database', 'mail'])
        ->and(new TicketCommentedNotification($ticket, null, 'Hi')->via($user))->toBe(['database']);
});

it('emails only the kinds the person chose', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['emailed_notifications' => ['comment']]);
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    expect(new TicketAssignedNotification($ticket, null)->via($user))->toBe(['database'])
        ->and(new TicketCommentedNotification($ticket, null, 'Hi')->via($user))->toBe(['database', 'mail']);
});

it('does not email someone whose address is a mailbox we read', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['email' => 'Support@Helpdesk.test']);
    MailboxEloquentModel::factory()->create(['address' => 'support@helpdesk.test']);
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    expect(new TicketAssignedNotification($ticket, null)->via($user))->toBe(['database']);
});

it('says who did what to which ticket, with the comment and a link to it', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $actor = UserEloquentModel::factory()->create(['name' => 'Sam Lee']);
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Office move']);
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'number' => 12, 'title' => 'Printer jam', 'project_id' => $project->id]);
    $ticket->load('project');

    $mail = new TicketCommentedNotification($ticket, $actor, 'Ordered a new toner.')->toMail($user);

    expect($mail->subject)->toBe('Sam Lee commented: [#12] Printer jam')
        ->and($mail->greeting)->toBe('Sam Lee commented')
        ->and($mail->actionUrl)->toBe(route('tickets.index', ['ticket' => $ticket->id]))
        ->and((string) app(Markdown::class)->renderText('notifications::email', $mail->data()))
        ->toContain('#12 Printer jam · Office move')
        ->toContain('Ordered a new toner.');
});

it('names the ticket when nobody did anything, and the page when it is not a ticket', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'number' => 7, 'title' => 'Renew domain']);
    $shift = ShiftEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'user_id' => $user->id]);

    $dueSoon = new TicketDueSoonNotification($ticket)->toMail($user);
    $stillOpen = new ShiftStillOpenNotification($shift, '8:00 am')->toMail($user);

    expect($dueSoon->subject)->toBe('Due tomorrow: [#7] Renew domain')
        ->and($stillOpen->subject)->toBe('You’re still clocked in')
        ->and($stillOpen->actionUrl)->toBe(route('attendance.time'));
});

it('shows a comment as written, without turning it into links or headings', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    $html = (string) new TicketCommentedNotification($ticket, null, '# Pay [here](https://evil.test) <b>now</b>', senderName: 'Sam')->toMail($user)->render();

    expect($html)
        ->not->toContain('href="https://evil.test"')
        ->not->toContain('<h1>Pay')
        ->not->toContain('<b>now</b>')
        ->toContain('Pay [here](https://evil.test)');
});
