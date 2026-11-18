<?php

declare(strict_types=1);

use App\Mailbox\MailboxEloquentModel;
use App\MailboxMessage\Jobs\SendMailboxMessageJob;
use App\MailboxMessage\MailboxMessageEloquentModel;
use App\Ticket\TicketEloquentModel;
use Illuminate\Support\Facades\Http;

it('sends the reply it was queued for', function (): void {
    Http::preventStrayRequests();
    $mailbox = MailboxEloquentModel::factory()->create();
    rememberMailboxToken($mailbox);
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $mailbox->team_id]);
    $message = MailboxMessageEloquentModel::factory()->outbound()->create(['mailbox_id' => $mailbox->id, 'ticket_id' => $ticket->id, 'status' => 'pending', 'to_recipients' => ['sam@acme.test']]);
    Http::fake([
        'graph.microsoft.com/v1.0/me/messages/draft-1/send' => Http::response(null, 202),
        'graph.microsoft.com/v1.0/me/messages' => Http::response(['id' => 'draft-1', 'internetMessageId' => '<draft-1@helpdesk.test>'], 201),
    ]);

    SendMailboxMessageJob::dispatch($message->id);

    expect($message->refresh()->status)->toBe('sent');
});

it('marks a reply as not sent when the send is cut short', function (): void {
    $message = MailboxMessageEloquentModel::factory()->outbound()->create(['status' => 'pending']);
    $sent = MailboxMessageEloquentModel::factory()->outbound()->create();

    new SendMailboxMessageJob($message->id)->failed(new RuntimeException('Timed out.'));
    new SendMailboxMessageJob($sent->id)->failed(new RuntimeException('Timed out.'));

    expect($message->refresh())
        ->status->toBe('failed')
        ->error->toBe('Sending this reply was interrupted. Check the mailbox before sending it again.')
        ->and($sent->refresh())
        ->status->toBe('sent')
        ->error->toBeNull();
});
