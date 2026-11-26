<?php

declare(strict_types=1);

use App\MailboxMessage\RawMail;

it('reads an email\'s headers, text and files from its source', function (): void {
    $mail = RawMail::read(emailSource(withFile: true));

    expect($mail)->not->toBeNull()
        ->and($mail->headers)->toMatchArray([
            'from' => 'Sam Lee <Sam@Acme.test>',
            'subject' => 'Printer offline',
            'message-id' => '<message-1@acme.test>',
        ])
        ->and(trim((string) $mail->text))->toBe('The printer on level 2 is offline.')
        ->and($mail->attachments)->toBe([[
            'name' => 'log.txt',
            'content_type' => 'text/plain',
            'size' => 9,
            'is_inline' => false,
            'content' => 'paper jam',
        ]]);
});

it('keeps the first header of a name and joins one folded over several lines', function (): void {
    $source = "Subject: Printer offline\r\n on level 2\r\nSubject: Something else\r\nFrom: sam@acme.test\r\n\r\nHello";

    expect(RawMail::read($source)?->headers)->toBe(['subject' => 'Printer offline on level 2', 'from' => 'sam@acme.test']);
});

it('reads an email whose lines end the Unix way, and one that ends at its headers', function (): void {
    expect(RawMail::read("Subject: Hello\nFrom: sam@acme.test\n\nFirst line\nSecond line\n"))
        ->headers->toBe(['subject' => 'Hello', 'from' => 'sam@acme.test'])
        ->bodyText()->toContain('First line', 'Second line')
        ->and(RawMail::read("Subject: Hello\nFrom: sam@acme.test"))
        ->headers->toBe(['subject' => 'Hello', 'from' => 'sam@acme.test'])
        ->attachments->toBe([]);
});

it('turns the HTML into text when the email has no other', function (): void {
    $source = "Subject: Hello\r\nContent-Type: text/html; charset=utf-8\r\n\r\n<p>The printer is <b>offline</b>.</p>";

    expect(RawMail::read($source))
        ->text->toBeNull()
        ->bodyText()->toBe('The printer is offline.');
});
