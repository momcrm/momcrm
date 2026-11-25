<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\Comment\CommentEloquentModel;
use App\Comment\Notifications\TicketCommentedNotification;
use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Group\GroupEloquentModel;
use App\Mailbox\Mailbox;
use App\Mailbox\MailboxEloquentModel;
use App\MailboxMessage\Actions\ProcessInboundMessage;
use App\MailboxMessage\MailboxMessage;
use App\MailboxMessage\MailboxMessageEloquentModel;
use App\Team\TeamEloquentModel;
use App\Ticket\Actions\UpdateTicket;
use App\Ticket\Notifications\TicketRaisedNotification;
use App\Ticket\Ticket;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Notification;

/**
 * Takes in an email from Microsoft for a mailbox.
 *
 * @param  array<string, mixed>  $message
 */
function takeInEmail(MailboxEloquentModel $mailbox, array $message = []): MailboxMessage
{
    return ProcessInboundMessage::fromMessage(
        Mailbox::fromEloquentModel($mailbox),
        messageFromGraph(graphMessage($message)),
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
        ->external_id->toBe('graph-1')
        ->internet_message_id->toBe('message-1@acme.test')
        ->from_address->toBe('sam@acme.test')
        ->to_recipients->toBe(['support@helpdesk.test'])
        ->subject->toBe('Printer offline')
        ->body_text->toBe('The printer on level 2 is offline.');
});

it("tells the mailbox group's members a ticket arrived, and nobody else", function (): void {
    $team = TeamEloquentModel::factory()->create();
    $group = GroupEloquentModel::factory()->create(['team_id' => $team->id]);
    $member = UserEloquentModel::factory()->inTeam($team->id)->create();
    $outsider = UserEloquentModel::factory()->inTeam($team->id)->create();
    $leaver = UserEloquentModel::factory()->create();
    $group->members()->attach([$member->id => ['role' => 'member'], $leaver->id => ['role' => 'member']]);
    $mailbox = MailboxEloquentModel::factory()->create(['team_id' => $team->id, 'group_id' => $group->id]);
    Notification::fake();

    takeInEmail($mailbox);

    Notification::assertSentTo($member, function (TicketRaisedNotification $notification) use ($member): bool {
        $payload = $notification->toArray($member);

        return $payload['kind'] === 'new_ticket'
            && $payload['actor_name'] === 'Sam Lee'
            && $payload['message'] === 'raised it by email'
            && $payload['excerpt'] === 'The printer on level 2 is offline.';
    });
    Notification::assertNotSentTo([$outsider, $leaver], TicketRaisedNotification::class);
});

it('tells everyone in the team a ticket arrived when the mailbox has no group', function (): void {
    $team = TeamEloquentModel::factory()->create();
    $members = UserEloquentModel::factory()->inTeam($team->id)->count(2)->create();
    $mailbox = MailboxEloquentModel::factory()->create(['team_id' => $team->id, 'group_id' => null]);
    Notification::fake();

    takeInEmail($mailbox);

    Notification::assertSentTo($members, TicketRaisedNotification::class);
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
    'when this app sent it' => [
        function () {
            config(['mail.from.address' => 'Hello@momcrm.test']);

            return MailboxEloquentModel::factory()->create();
        },
        ['from' => ['emailAddress' => ['address' => 'hello@momcrm.test']], 'subject' => 'Sam Lee raised a ticket: [#1] Printer offline'],
    ],
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

it('keeps a message that has no Message-ID once, by its id in the mailbox', function (): void {
    $mailbox = MailboxEloquentModel::factory()->create();
    $message = ['internetMessageId' => null, 'id' => 'graph-9'];

    takeInEmail($mailbox, $message);
    takeInEmail($mailbox, $message);

    expect(MailboxMessageEloquentModel::query()->withoutGlobalScopes()->sole()->internet_message_id)->toBe("mailbox-{$mailbox->id}:graph-9")
        ->and(TicketEloquentModel::query()->withoutGlobalScopes()->count())->toBe(1);
});

/**
 * An email answering the one that opened the ticket.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function replyEmail(array $overrides = []): array
{
    return [
        'id' => 'graph-reply',
        'subject' => 'RE: Printer offline',
        'internetMessageId' => '<reply-1@acme.test>',
        'body' => ['contentType' => 'text', 'content' => "Still offline this morning.\r\n\r\nOn Tuesday Sam Lee wrote:\r\n> The printer on level 2 is offline."],
        'uniqueBody' => ['contentType' => 'text', 'content' => "Still offline this morning.\r\n"],
        'internetMessageHeaders' => [
            ['name' => 'In-Reply-To', 'value' => '<message-1@acme.test>'],
            ['name' => 'References', 'value' => '<message-1@acme.test>'],
        ],
        ...$overrides,
    ];
}

it('adds a reply to the ticket it answers, without the quoted thread, and tells whoever is on the ticket', function (): void {
    $team = TeamEloquentModel::factory()->create();
    $assignee = UserEloquentModel::factory()->inTeam($team->id)->create();
    $mailbox = MailboxEloquentModel::factory()->create(['team_id' => $team->id]);
    takeInEmail($mailbox);
    $ticket = TicketEloquentModel::query()->withoutGlobalScopes()->sole();
    $ticket->update(['assignee_id' => $assignee->id]);
    Notification::fake();

    $kept = takeInEmail($mailbox, replyEmail(['from' => ['emailAddress' => ['name' => 'Kim Park', 'address' => 'Kim@Acme.test']]]));

    $comment = CommentEloquentModel::query()->withoutGlobalScopes()->sole();

    expect($comment)
        ->team_id->toBe($team->id)
        ->ticket_id->toBe($ticket->id)
        ->user_id->toBeNull()
        ->body->toBe('Still offline this morning.')
        ->visibility->toBe('public')
        ->sender_name->toBe('Kim Park')
        ->sender_email->toBe('kim@acme.test');

    expect(TicketEloquentModel::query()->withoutGlobalScopes()->count())->toBe(1)
        ->and($kept->getStatus()->toEnum()->value)->toBe('processed')
        ->and($kept->getTicketId()->toString())->toBe($ticket->id)
        ->and($kept->getCommentId()->toString())->toBe($comment->id)
        ->and(ActivityEloquentModel::query()->where('type', ActivityTypeEnum::TicketCommented->value)->sole()->details)
        ->toEqual(['number' => $ticket->number, 'title' => 'Printer offline', 'actor_name' => 'Kim Park']);

    Notification::assertSentTo($assignee, function (TicketCommentedNotification $notification) use ($assignee): bool {
        $payload = $notification->toArray($assignee);

        return $payload['actor_name'] === 'Kim Park'
            && $payload['actor_id'] === null
            && $payload['message'] === 'replied by email'
            && $payload['excerpt'] === 'Still offline this morning.';
    });
});

it('tells the group about a reply only while nobody is assigned the ticket', function (): void {
    $team = TeamEloquentModel::factory()->create();
    $group = GroupEloquentModel::factory()->create(['team_id' => $team->id]);
    $member = UserEloquentModel::factory()->inTeam($team->id)->create();
    $group->members()->attach($member->id, ['role' => 'member']);
    $mailbox = MailboxEloquentModel::factory()->create(['team_id' => $team->id, 'group_id' => $group->id]);
    takeInEmail($mailbox);
    Notification::fake();

    takeInEmail($mailbox, replyEmail());

    Notification::assertSentTo($member, TicketCommentedNotification::class);

    TicketEloquentModel::query()->withoutGlobalScopes()->sole()->update(['assignee_id' => UserEloquentModel::factory()->inTeam($team->id)->create()->id]);
    Notification::fake();

    takeInEmail($mailbox, replyEmail(['id' => 'graph-reply-2', 'internetMessageId' => '<reply-2@acme.test>']));

    Notification::assertNotSentTo($member, TicketCommentedNotification::class);
});

it('follows the thread back to the nearest message that is on a ticket', function (): void {
    $mailbox = MailboxEloquentModel::factory()->create();
    takeInEmail($mailbox);

    takeInEmail($mailbox, replyEmail(['internetMessageHeaders' => [
        ['name' => 'In-Reply-To', 'value' => '<a-reply-we-never-saw@helpdesk.test>'],
        ['name' => 'References', 'value' => '<message-1@acme.test> <a-reply-we-never-saw@helpdesk.test>'],
    ]]));

    expect(TicketEloquentModel::query()->withoutGlobalScopes()->count())->toBe(1)
        ->and(CommentEloquentModel::query()->withoutGlobalScopes()->count())->toBe(1);
});

it('adds a reply even when the mailbox does not open tickets', function (): void {
    $mailbox = MailboxEloquentModel::factory()->create();
    takeInEmail($mailbox);
    $mailbox->update(['auto_create_tickets' => false]);

    takeInEmail($mailbox, replyEmail());

    expect(CommentEloquentModel::query()->withoutGlobalScopes()->count())->toBe(1);
});

it('reopens a closed ticket when a reply arrives', function (): void {
    $mailbox = MailboxEloquentModel::factory()->create();
    takeInEmail($mailbox);
    $ticket = TicketEloquentModel::query()->withoutGlobalScopes()->sole();
    UpdateTicket::fromTicket(Ticket::fromEloquentModel($ticket)->setStatusId(ticketStatusId($ticket->team_id, 'Closed')));

    takeInEmail($mailbox, replyEmail());

    expect($ticket->refresh())
        ->status_id->toBe(ticketStatusId($ticket->team_id, 'New'))
        ->closed_at->toBeNull()
        ->and(ActivityEloquentModel::query()->where('type', ActivityTypeEnum::TicketReopened->value)->count())->toBe(1);
});

it('opens a new ticket for a reply to an archived one', function (): void {
    $mailbox = MailboxEloquentModel::factory()->create();
    takeInEmail($mailbox);
    $archived = TicketEloquentModel::query()->withoutGlobalScopes()->sole();
    $archived->update(['archived_at' => now()]);

    takeInEmail($mailbox, replyEmail());

    expect(TicketEloquentModel::query()->withoutGlobalScopes()->whereKeyNot($archived->id)->sole()->title)->toBe('RE: Printer offline')
        ->and(CommentEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
});

it('does not follow a thread into another team\'s ticket', function (): void {
    $theirs = MailboxEloquentModel::factory()->create();
    takeInEmail($theirs);
    $mailbox = MailboxEloquentModel::factory()->create();

    takeInEmail($mailbox, replyEmail());

    expect(TicketEloquentModel::query()->withoutGlobalScopes()->where('team_id', $mailbox->team_id)->sole()->title)->toBe('RE: Printer offline')
        ->and(CommentEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
});

it('joins the ticket numbered in the subject when the sender is already on it', function (Closure $ticketFor, string $from): void {
    $mailbox = MailboxEloquentModel::factory()->create();
    $ticket = $ticketFor($mailbox);

    takeInEmail($mailbox, [
        'subject' => "Re: Printer offline [#{$ticket->number}]",
        'from' => ['emailAddress' => ['address' => $from]],
    ]);

    expect(CommentEloquentModel::query()->withoutGlobalScopes()->sole()->ticket_id)->toBe($ticket->id)
        ->and(TicketEloquentModel::query()->withoutGlobalScopes()->count())->toBe(1);
})->with([
    'the requester' => [
        fn (MailboxEloquentModel $mailbox) => TicketEloquentModel::factory()->create(['team_id' => $mailbox->team_id, 'requester_email' => 'sam@acme.test']),
        'Sam@Acme.test',
    ],
    'the ticket\'s contact' => [
        function (MailboxEloquentModel $mailbox) {
            $contact = ContactEloquentModel::factory()->create(['team_id' => $mailbox->team_id, 'email' => 'Kim@Acme.test']);

            return TicketEloquentModel::factory()->create(['team_id' => $mailbox->team_id, 'customer_id' => $contact->customer_id, 'contact_id' => $contact->id]);
        },
        'kim@acme.test',
    ],
    'someone who wrote in on it before' => [
        function (MailboxEloquentModel $mailbox) {
            $ticket = TicketEloquentModel::factory()->create(['team_id' => $mailbox->team_id]);
            MailboxMessageEloquentModel::factory()->create(['mailbox_id' => $mailbox->id, 'ticket_id' => $ticket->id, 'from_address' => 'lee@acme.test']);

            return $ticket;
        },
        'lee@acme.test',
    ],
]);

it('opens a new ticket when a stranger puts a ticket number in the subject', function (): void {
    $mailbox = MailboxEloquentModel::factory()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $mailbox->team_id, 'requester_email' => 'sam@acme.test']);
    MailboxMessageEloquentModel::factory()->outbound()->create(['mailbox_id' => $mailbox->id, 'ticket_id' => $ticket->id, 'from_address' => 'mallory@elsewhere.test']);

    takeInEmail($mailbox, [
        'subject' => "Please pay this invoice [#{$ticket->number}]",
        'from' => ['emailAddress' => ['address' => 'mallory@elsewhere.test']],
    ]);

    expect(CommentEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse()
        ->and(TicketEloquentModel::query()->withoutGlobalScopes()->whereKeyNot($ticket->id)->sole()->requester_email)->toBe('mallory@elsewhere.test');
});

it('says so when a reply has no text', function (): void {
    $mailbox = MailboxEloquentModel::factory()->create();
    takeInEmail($mailbox);

    takeInEmail($mailbox, replyEmail(['body' => ['contentType' => 'text', 'content' => ''], 'uniqueBody' => ['contentType' => 'text', 'content' => " \r\n"]]));

    expect(CommentEloquentModel::query()->withoutGlobalScopes()->sole()->body)->toBe('(no text)');
});

it('adds the customer\'s answer to a reply the team sent', function (): void {
    $mailbox = MailboxEloquentModel::factory()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $mailbox->team_id]);
    MailboxMessageEloquentModel::factory()->outbound()->create(['mailbox_id' => $mailbox->id, 'ticket_id' => $ticket->id, 'internet_message_id' => 'draft-1@helpdesk.test']);

    takeInEmail($mailbox, replyEmail(['internetMessageHeaders' => [['name' => 'In-Reply-To', 'value' => '<draft-1@helpdesk.test>']]]));

    expect(CommentEloquentModel::query()->withoutGlobalScopes()->sole()->ticket_id)->toBe($ticket->id)
        ->and(TicketEloquentModel::query()->withoutGlobalScopes()->count())->toBe(1);
});
