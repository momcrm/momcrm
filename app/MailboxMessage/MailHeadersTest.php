<?php

declare(strict_types=1);

use App\MailboxMessage\MailHeaders;

it('reads an email from its headers, whatever shape the sender\'s address takes', function (string $from, ?string $name, ?string $address): void {
    $mail = MailHeaders::toInboundMail(['from' => $from, 'message-id' => '<Msg-1@Acme.test>'], 'outside-1', " Hello \n", null);

    expect($mail->fromName)->toBe($name)
        ->and($mail->fromAddress)->toBe($address)
        ->and($mail->messageId)->toBe('Msg-1@Acme.test')
        ->and($mail->externalId)->toBe('outside-1')
        ->and($mail->bodyText)->toBe('Hello')
        ->and($mail->to)->toBe([])
        ->and($mail->subject)->toBeNull()
        ->and($mail->hasAttachments)->toBeFalse();
})->with([
    'a name and an address' => ['Kim Lee <Kim@Acme.test>', 'Kim Lee', 'kim@acme.test'],
    'a quoted name with a comma' => ['"Lee, Kim" <kim@acme.test>', 'Lee, Kim', 'kim@acme.test'],
    'an encoded name' => ['=?UTF-8?Q?Kim_=C3=85ker?= <kim@acme.test>', 'Kim Åker', 'kim@acme.test'],
    'only an address' => ['kim@acme.test', null, 'kim@acme.test'],
    'an address in brackets' => ['<kim@acme.test>', null, 'kim@acme.test'],
    'nothing' => ['', null, null],
]);

it('reads the thread, every recipient, and what was written apart from the quoted history', function (): void {
    $mail = MailHeaders::toInboundMail([
        'to' => 'Help Desk <Help@Helpdesk.test>, other@acme.test',
        'subject' => '=?UTF-8?B?UHJpbnRlciBvZmZsaW5l?=',
        'in-reply-to' => '<sent-1@helpdesk.test>',
        'references' => '<msg-0@acme.test> <sent-1@helpdesk.test>',
    ], 'outside-2', "It is the one on level 2.\n\n-----Original Message-----\nWhich printer is it?", null, true);

    expect($mail->to)->toBe(['help@helpdesk.test', 'other@acme.test'])
        ->and($mail->subject)->toBe('Printer offline')
        ->and($mail->inReplyTo)->toBe('sent-1@helpdesk.test')
        ->and($mail->references)->toBe(['msg-0@acme.test', 'sent-1@helpdesk.test'])
        ->and($mail->replyText)->toBe('It is the one on level 2.')
        ->and($mail->bodyText)->toContain('Original Message')
        ->and($mail->hasAttachments)->toBeTrue();
});
