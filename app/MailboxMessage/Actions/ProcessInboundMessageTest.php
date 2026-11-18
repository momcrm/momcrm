<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Group\GroupEloquentModel;
use App\Mailbox\Mailbox;
use App\Mailbox\MailboxEloquentModel;
use App\MailboxMessage\Actions\ProcessInboundMessage;
use App\MailboxMessage\MailboxMessage;
use App\MailboxMessage\MailboxMessageEloquentModel;
use App\Team\TeamEloquentModel;
use App\Ticket\TicketEloquentModel;

/**
 * Takes in an email from Microsoft for a mailbox.
 *
 * @param  array<string, mixed>  $message
 */
function takeInEmail(MailboxEloquentModel $mailbox, array $message = []): MailboxMessage
{
    return ProcessInboundMessage::fromMessage(
        Mailbox::fromEloquentModel($mailbox),
        MailboxMessage::fromMicrosoftGraph(graphMessage($message)),
    );
}

it('opens a ticket for mail from someone who is not a contact, keeping who wrote in', function (): void {
    $team = TeamEloquentModel::factory()->create();
    $group = GroupEloquentModel::factory()->create(['team_id' => $team->id]);
    $mailbox = MailboxEloquentModel::factory()->create(['team_id' => $team->id, 'address' => 'support@helpdesk.test', 'group_id' => $group->id]);
    TicketEloquentModel::factory()->create(['team_id' => $team->id, 'number' => 7]);

    $kept = takeInEmail($mailbox);

    $ticket = TicketEloquentModel::query()->withoutGlobalScopes()->where('title', 'Printer offline')->sole();

    expect($ticket)
        ->team_id->toBe($team->id)
        ->number->toBe(8)
        ->description->toBe('The printer on level 2 is offline.')
        ->mailbox_id->toBe($mailbox->id)
        ->group_id->toBe($group->id)
        ->requester_email->toBe('sam@acme.test')
        ->requester_name->toBe('Sam Lee')
        ->customer_id->toBeNull()
        ->contact_id->toBeNull()
        ->assignee_id->toBeNull()
        ->status_id->toBe(ticketStatusId($team->id, 'New'))
        ->priority_id->not->toBeNull();

    expect(MailboxMessageEloquentModel::query()->withoutGlobalScopes()->sole())
        ->id->toBe($kept->getId()->toString())
        ->team_id->toBe($team->id)
        ->mailbox_id->toBe($mailbox->id)
        ->direction->toBe('inbound')
        ->status->toBe('processed')
        ->ticket_id->toBe($ticket->id)
        ->microsoft_id->toBe('graph-1')
        ->internet_message_id->toBe('message-1@acme.test')
        ->from_address->toBe('sam@acme.test')
        ->to_recipients->toBe(['support@helpdesk.test'])
        ->subject->toBe('Printer offline')
        ->body_text->toBe('The printer on level 2 is offline.');
});

it('gives the ticket the contact and customer the sender is, whatever the letter case', function (): void {
    $team = TeamEloquentModel::factory()->create();
    $mailbox = MailboxEloquentModel::factory()->create(['team_id' => $team->id]);
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $team->id]);
    $contact = ContactEloquentModel::factory()->create(['team_id' => $team->id, 'customer_id' => $customer->id, 'email' => 'SAM@acme.test', 'created_at' => now()->subDay()]);
    ContactEloquentModel::factory()->create(['team_id' => $team->id, 'email' => 'sam@acme.test']);
    ContactEloquentModel::factory()->create(['email' => 'sam@acme.test', 'created_at' => now()->subWeek()]);

    takeInEmail($mailbox);

    expect(TicketEloquentModel::query()->withoutGlobalScopes()->sole())
        ->contact_id->toBe($contact->id)
        ->customer_id->toBe($customer->id)
        ->requester_email->toBe('sam@acme.test');
});

it('keeps a message once per team, however it arrives again', function (): void {
    $team = TeamEloquentModel::factory()->create();
    $mailbox = MailboxEloquentModel::factory()->create(['team_id' => $team->id]);
    $second = MailboxEloquentModel::factory()->create(['team_id' => $team->id]);
    takeInEmail($mailbox);

    expect(takeInEmail($mailbox)->isNull())->toBeTrue()
        ->and(takeInEmail($second, ['id' => 'graph-in-second-mailbox'])->isNull())->toBeTrue()
        ->and(TicketEloquentModel::query()->withoutGlobalScopes()->count())->toBe(1)
        ->and(MailboxMessageEloquentModel::query()->withoutGlobalScopes()->count())->toBe(1);
});

it('opens a ticket in each team the same message reaches', function (): void {
    $mailbox = MailboxEloquentModel::factory()->create();
    $theirs = MailboxEloquentModel::factory()->create();

    takeInEmail($mailbox);
    takeInEmail($theirs);

    expect(TicketEloquentModel::query()->withoutGlobalScopes()->pluck('team_id')->all())
        ->toEqualCanonicalizing([$mailbox->team_id, $theirs->team_id]);
});

it('keeps the mail without opening a ticket', function (Closure $mailbox, array $message): void {
    $kept = takeInEmail($mailbox(), $message);

    expect(TicketEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse()
        ->and($kept->isNotNull())->toBeTrue()
        ->and(MailboxMessageEloquentModel::query()->withoutGlobalScopes()->sole())
        ->status->toBe('ignored')
        ->ticket_id->toBeNull();
})->with([
    'when the mailbox does not open tickets' => [fn () => MailboxEloquentModel::factory()->create(['auto_create_tickets' => false]), []],
    'when the mailbox sent it to itself' => [
        fn () => MailboxEloquentModel::factory()->create(['address' => 'support@helpdesk.test']),
        ['from' => ['emailAddress' => ['address' => 'Support@Helpdesk.test']]],
    ],
    'when it has no sender' => [fn () => MailboxEloquentModel::factory()->create(), ['from' => null]],
]);

it('fits the subject and text into the ticket', function (): void {
    $mailbox = MailboxEloquentModel::factory()->create();

    takeInEmail($mailbox, [
        'subject' => str_repeat('é', 300),
        'body' => ['contentType' => 'text', 'content' => str_repeat('😀', 20000)],
    ]);

    $ticket = TicketEloquentModel::query()->withoutGlobalScopes()->sole();

    expect($ticket->title)->toBe(str_repeat('é', 255))
        ->and($ticket->description)->toBe(str_repeat('😀', 15000))
        ->and(MailboxMessageEloquentModel::query()->withoutGlobalScopes()->sole()->body_text)->toBe(str_repeat('😀', 20000));
});

it('titles a message with no subject', function (): void {
    takeInEmail(MailboxEloquentModel::factory()->create(), ['subject' => null]);

    expect(TicketEloquentModel::query()->withoutGlobalScopes()->sole()->title)->toBe('(no subject)');
});

it('keeps a message that has no Message-ID once, by Microsoft\'s id', function (): void {
    $mailbox = MailboxEloquentModel::factory()->create();
    $message = ['internetMessageId' => null, 'id' => 'graph-9'];

    takeInEmail($mailbox, $message);
    takeInEmail($mailbox, $message);

    expect(MailboxMessageEloquentModel::query()->withoutGlobalScopes()->sole()->internet_message_id)->toBe('microsoft:graph-9')
        ->and(TicketEloquentModel::query()->withoutGlobalScopes()->count())->toBe(1);
});
