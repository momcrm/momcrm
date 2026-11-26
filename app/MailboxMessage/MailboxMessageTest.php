<?php

declare(strict_types=1);

use App\MailboxMessage\MailboxMessage;
use App\MailboxMessage\MailboxMessageEloquentModel;
use Mom\Connectors\Mail\InboundMail;

it('keeps an email from Microsoft with addresses in lower case and ids without brackets', function (): void {
    $message = messageFromGraph(graphMessage([
        'body' => ['contentType' => 'text', 'content' => "Still broken.\r\n\r\nOn Monday Support wrote:\r\n> Try turning it off and on."],
        'uniqueBody' => ['contentType' => 'text', 'content' => "Still broken.\r\n"],
        'internetMessageHeaders' => [
            ['name' => 'in-reply-to', 'value' => '<reply-2@helpdesk.test>'],
            ['name' => 'References', 'value' => "<message-1@acme.test>\r\n <reply-2@helpdesk.test>"],
        ],
    ]));

    expect($message->getExternalId()->toString())->toBe('graph-1')
        ->and($message->getInternetMessageId()->toString())->toBe('message-1@acme.test')
        ->and($message->getInReplyTo()->toString())->toBe('reply-2@helpdesk.test')
        ->and($message->getReferenceIds()->toArray())->toBe(['message-1@acme.test', 'reply-2@helpdesk.test'])
        ->and($message->getFromAddress()->toString())->toBe('sam@acme.test')
        ->and($message->getFromName()->toString())->toBe('Sam Lee')
        ->and($message->getToRecipients()->toArray())->toBe(['support@helpdesk.test'])
        ->and($message->getSubject()->toString())->toBe('Printer offline')
        ->and($message->getBodyText()->toString())->toBe("Still broken.\r\n\r\nOn Monday Support wrote:\r\n> Try turning it off and on.")
        ->and($message->getReplyText()->toString())->toBe('Still broken.')
        ->and($message->getReceivedAt()->toCarbon()->toIso8601ZuluString())->toBe('2026-11-18T01:15:00Z')
        ->and($message->getDirection()->toEnum()->value)->toBe('inbound');
});

it('keeps an email as its sender laid it out, as it arrived', function (): void {
    $html = '<p>The printer is <b>offline</b>.</p><script>alert(1)</script>';

    expect(MailboxMessage::fromInboundMail(new InboundMail('graph-1', 'message-1@acme.test', bodyText: 'The printer is offline.', bodyHtml: $html)))
        ->getBodyHtml()->toNullableString()->toBe($html)
        ->toArray()->not->toHaveKey('body_html');
});

it('keeps only the text of an email whose HTML is not worth keeping', function (?string $html): void {
    $message = MailboxMessage::fromInboundMail(new InboundMail('graph-1', 'message-1@acme.test', bodyText: 'The printer is offline.', bodyHtml: $html));

    expect($message->getBodyHtml()->isNull())->toBeTrue()
        ->and($message->getBodyText()->toString())->toBe('The printer is offline.');
})->with([
    'none' => [null],
    'empty' => ["  \r\n"],
    'too long' => [str_repeat('<p>Hello</p>', 100_000)],
    'not text the database can hold' => ["<p>Caf\xE9</p>"],
]);

it('lists the thread a message follows, the message it answers first', function (): void {
    $message = messageFromGraph(graphMessage(['internetMessageHeaders' => [
        ['name' => 'In-Reply-To', 'value' => '<reply-2@helpdesk.test>'],
        ['name' => 'References', 'value' => '<message-1@acme.test> <reply-2@helpdesk.test>'],
    ]]));

    expect($message->threadIds())->toBe(['reply-2@helpdesk.test', 'message-1@acme.test']);
});

it('leaves out what a message does not have', function (): void {
    $message = messageFromGraph([
        'id' => 'graph-2',
        'subject' => '  ',
        'from' => null,
        'receivedDateTime' => 'not a date',
        'body' => ['contentType' => 'text', 'content' => 'Hello'],
    ]);

    expect($message->getInternetMessageId()->isNull())->toBeTrue()
        ->and($message->getFromAddress()->isNull())->toBeTrue()
        ->and($message->getSubject()->isNull())->toBeTrue()
        ->and($message->getReceivedAt()->isNull())->toBeTrue()
        ->and($message->getToRecipients()->isNull())->toBeTrue()
        ->and($message->getReplyText()->toString())->toBe('Hello')
        ->and($message->threadIds())->toBe([]);
});

it('hands a stored message back as the email a reply answers', function (): void {
    $stored = MailboxMessage::fromEloquentModel(MailboxMessageEloquentModel::factory()->create([
        'external_id' => 'graph-1',
        'internet_message_id' => 'message-1@acme.test',
        'in_reply_to' => 'reply-2@helpdesk.test',
        'reference_ids' => ['message-0@acme.test'],
        'from_address' => 'sam@acme.test',
        'to_recipients' => ['support@helpdesk.test'],
        'subject' => 'Printer offline',
    ]));

    expect($stored->toInboundMail())
        ->externalId->toBe('graph-1')
        ->messageId->toBe('message-1@acme.test')
        ->inReplyTo->toBe('reply-2@helpdesk.test')
        ->references->toBe(['message-0@acme.test'])
        ->fromAddress->toBe('sam@acme.test')
        ->to->toBe(['support@helpdesk.test'])
        ->subject->toBe('Printer offline');
});
