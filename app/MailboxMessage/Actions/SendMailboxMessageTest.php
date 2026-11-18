<?php

declare(strict_types=1);

use App\Mailbox\Actions\GetMailboxAccessToken;
use App\Mailbox\Mailbox;
use App\Mailbox\MailboxEloquentModel;
use App\MailboxMessage\Actions\SendMailboxMessage;
use App\MailboxMessage\MailboxMessage;
use App\MailboxMessage\MailboxMessageEloquentModel;
use App\Ticket\TicketEloquentModel;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\freezeSecond;

/**
 * A reply waiting to be sent to sam@acme.test from a mailbox we hold a token
 * for, on a ticket numbered 42.
 *
 * @param  array<string, mixed>  $attributes
 */
function waitingReply(array $attributes = [], ?MailboxEloquentModel $mailbox = null): MailboxMessageEloquentModel
{
    $mailbox ??= MailboxEloquentModel::factory()->create(['address' => 'support@helpdesk.test']);
    GetMailboxAccessToken::remember(Mailbox::fromEloquentModel($mailbox), 'access-token', 3600);
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $mailbox->team_id, 'number' => 42, 'title' => 'Printer offline', 'requester_email' => 'sam@acme.test']);

    return MailboxMessageEloquentModel::factory()->outbound()->create([
        'mailbox_id' => $mailbox->id,
        'ticket_id' => $ticket->id,
        'status' => 'pending',
        'microsoft_id' => null,
        'internet_message_id' => 'pending:1',
        'from_address' => 'support@helpdesk.test',
        'to_recipients' => ['sam@acme.test'],
        'subject' => null,
        'body_text' => "A new toner arrives tomorrow.\nSorry for the wait.",
        'received_at' => null,
        ...$attributes,
    ]);
}

function sendReply(MailboxMessageEloquentModel $message): MailboxMessage
{
    return SendMailboxMessage::fromMessage(MailboxMessage::fromEloquentModel($message));
}

it('answers the customer\'s last message so the reply threads under it', function (): void {
    Http::preventStrayRequests();
    $now = freezeSecond();
    $message = waitingReply();
    MailboxMessageEloquentModel::factory()->create(['mailbox_id' => $message->mailbox_id, 'ticket_id' => $message->ticket_id, 'microsoft_id' => 'graph-first', 'received_at' => now()->subDay()]);
    MailboxMessageEloquentModel::factory()->create(['mailbox_id' => $message->mailbox_id, 'ticket_id' => $message->ticket_id, 'microsoft_id' => 'graph-latest', 'received_at' => now()->subHour()]);
    Http::fake([
        'graph.microsoft.com/v1.0/me/messages/graph-latest/createReply' => Http::response(['id' => 'draft-1', 'subject' => 'RE: Printer offline', 'internetMessageId' => '<draft-1@helpdesk.test>'], 201),
        'graph.microsoft.com/v1.0/me/messages/draft-1/send' => Http::response(null, 202),
        'graph.microsoft.com/v1.0/me/messages/draft-1' => Http::response(['id' => 'draft-1', 'subject' => 'RE: Printer offline [#42]', 'internetMessageId' => '<draft-1@helpdesk.test>']),
    ]);

    sendReply($message);

    expect($message->refresh())
        ->status->toBe('sent')
        ->error->toBeNull()
        ->microsoft_id->toBe('draft-1')
        ->internet_message_id->toBe('draft-1@helpdesk.test')
        ->subject->toBe('RE: Printer offline [#42]')
        ->and($message->received_at?->equalTo($now))->toBeTrue();

    Http::assertSentCount(3);
    Http::assertSent(fn (Request $request): bool => $request->method() === 'PATCH'
        && $request->data() === [
            'subject' => 'RE: Printer offline [#42]',
            'body' => ['contentType' => 'html', 'content' => "A new toner arrives tomorrow.<br>\nSorry for the wait."],
            'toRecipients' => [['emailAddress' => ['address' => 'sam@acme.test']]],
        ]);
    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST' && str_ends_with($request->url(), '/messages/draft-1/send'));
});

it('starts a thread, with the ticket number in the subject, when the customer has not written in', function (Closure $arrange): void {
    Http::preventStrayRequests();
    $message = waitingReply();
    $arrange($message);
    Http::fake([
        'graph.microsoft.com/v1.0/me/messages/graph-gone/createReply' => Http::response(['error' => ['code' => 'ErrorItemNotFound']], 404),
        'graph.microsoft.com/v1.0/me/messages/draft-2/send' => Http::response(null, 202),
        'graph.microsoft.com/v1.0/me/messages' => Http::response(['id' => 'draft-2', 'subject' => 'Printer offline [#42]', 'internetMessageId' => '<draft-2@helpdesk.test>'], 201),
    ]);

    sendReply($message);

    expect($message->refresh())
        ->status->toBe('sent')
        ->internet_message_id->toBe('draft-2@helpdesk.test')
        ->subject->toBe('Printer offline [#42]');

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && str_ends_with($request->url(), '/me/messages')
        && $request['subject'] === 'Printer offline [#42]'
        && $request['toRecipients'] === [['emailAddress' => ['address' => 'sam@acme.test']]]);
})->with([
    'at all' => [fn () => null],
    'in a message still in the mailbox' => [fn (MailboxMessageEloquentModel $message) => MailboxMessageEloquentModel::factory()->create([
        'mailbox_id' => $message->mailbox_id,
        'ticket_id' => $message->ticket_id,
        'microsoft_id' => 'graph-gone',
    ])],
]);

it('sends what was typed as text, not as markup', function (): void {
    Http::preventStrayRequests();
    $message = waitingReply(['body_text' => 'Use <b>Ctrl</b> & "P" <script>alert(1)</script>']);
    Http::fake([
        'graph.microsoft.com/v1.0/me/messages/draft-2/send' => Http::response(null, 202),
        'graph.microsoft.com/v1.0/me/messages' => Http::response(['id' => 'draft-2', 'internetMessageId' => '<draft-2@helpdesk.test>'], 201),
    ]);

    sendReply($message);

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/me/messages')
        && str_contains($request['body']['content'], '&lt;script&gt;alert(1)&lt;/script&gt;')
        && str_contains($request['body']['content'], '&lt;b&gt;Ctrl&lt;/b&gt; &amp;')
        && ! str_contains($request['body']['content'], '<script>'));
});

it('sends from a shared mailbox by its address', function (): void {
    Http::preventStrayRequests();
    $message = waitingReply(mailbox: MailboxEloquentModel::factory()->shared()->create(['address' => 'support@helpdesk.test']));
    Http::fake([
        'graph.microsoft.com/v1.0/users/support%40helpdesk.test/messages/draft-2/send' => Http::response(null, 202),
        'graph.microsoft.com/v1.0/users/support%40helpdesk.test/messages' => Http::response(['id' => 'draft-2', 'internetMessageId' => '<draft-2@helpdesk.test>'], 201),
    ]);

    sendReply($message);

    expect($message->refresh()->status)->toBe('sent');
});

it('keeps why a reply was not sent, and does not try again', function (): void {
    Http::preventStrayRequests();
    $message = waitingReply();
    Http::fake([
        'graph.microsoft.com/v1.0/me/messages/draft-2/send' => Http::response(['error' => ['code' => 'ErrorSendAsDenied', 'message' => 'The user account which was used to submit this request does not have the right to send mail on behalf of the specified sending account.']], 403),
        'graph.microsoft.com/v1.0/me/messages' => Http::response(['id' => 'draft-2', 'internetMessageId' => '<draft-2@helpdesk.test>'], 201),
    ]);

    sendReply($message);
    sendReply($message->refresh());

    expect($message->refresh())
        ->status->toBe('failed')
        ->error->toBe('The user account which was used to submit this request does not have the right to send mail on behalf of the specified sending account.')
        ->internet_message_id->toBe('pending:1');

    Http::assertSentCount(2);
});

it('does not retry a request that changes the mailbox when Microsoft is busy', function (): void {
    Http::preventStrayRequests();
    $message = waitingReply();
    Http::fake(['graph.microsoft.com/v1.0/me/messages' => Http::response(['error' => ['code' => 'serviceNotAvailable']], 503)]);

    sendReply($message);

    expect($message->refresh())
        ->status->toBe('failed')
        ->error->toBe('Microsoft could not be reached, so this reply was not sent.');

    Http::assertSentCount(1);
});

it('does not send from a mailbox that needs reconnecting', function (): void {
    Http::preventStrayRequests();
    Http::fake();
    $message = waitingReply(mailbox: MailboxEloquentModel::factory()->needsReconnect()->create());

    sendReply($message);

    expect($message->refresh())
        ->status->toBe('failed')
        ->error->toBe('Microsoft no longer accepts the sign-in for this mailbox. Connect it again.');

    Http::assertNothingSent();
});

it('reports a failure of our own and says only that something went wrong', function (): void {
    Http::preventStrayRequests();
    Exceptions::fake();
    $message = waitingReply();
    Http::fake(['graph.microsoft.com/v1.0/me/messages' => Http::response(['subject' => 'A draft with no id'], 201)]);

    sendReply($message);

    expect($message->refresh())
        ->status->toBe('failed')
        ->error->toBe('Something went wrong sending this reply.');

    Exceptions::assertReported(UnexpectedValueException::class);
});

it('leaves a message alone once it is no longer waiting', function (): void {
    Http::preventStrayRequests();
    Http::fake();
    $message = waitingReply(['status' => 'sent']);

    sendReply($message);

    expect($message->refresh()->status)->toBe('sent');

    Http::assertNothingSent();
});
