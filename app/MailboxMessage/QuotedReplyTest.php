<?php

declare(strict_types=1);

use App\MailboxMessage\QuotedReply;

it('keeps what was written above the quoted history', function (string $message, string $expected): void {
    expect(QuotedReply::strip($message))->toBe($expected);
})->with([
    'Gmail' => [
        "Thanks, that fixed it.\n\nOn Mon, 2 Nov 2026 at 09:14, Help Desk <help@helpdesk.test> wrote:\n\n> Please restart the printer.\n> Regards",
        'Thanks, that fixed it.',
    ],
    'Gmail, with the introduction wrapped onto two lines' => [
        "Still broken.\n\nOn Mon, 2 Nov 2026 at 09:14, Help Desk <help-with-a-long-address@helpdesk.test>\nwrote:\n\n> Please restart the printer.",
        'Still broken.',
    ],
    'Outlook' => [
        "It is the one on level 2.\r\n\r\n________________________________\r\nFrom: Help Desk <help@helpdesk.test>\r\nSent: Monday, 2 November 2026 9:14 AM\r\nTo: Kim Lee <kim@acme.test>\r\nSubject: RE: Printer offline [#12]\r\n\r\nWhich printer is it?",
        'It is the one on level 2.',
    ],
    'an original-message rule' => [
        "Yes please.\n\n-----Original Message-----\nFrom: help@helpdesk.test\nShall we order a new one?",
        'Yes please.',
    ],
    'only quote marks at the end' => [
        "See my answer.\n\n> Which printer is it?\n>\n> Regards",
        'See my answer.',
    ],
]);

it('keeps the whole message when nothing in it is quoted history', function (string $message): void {
    expect(QuotedReply::strip($message))->toBe($message);
})->with([
    'a plain message' => ["The printer on level 2 is offline.\n\nKim"],
    'a line that only mentions From' => ["From: the look of it, the toner is empty.\nCan someone come?"],
    'quote marks in the middle of what was written' => ["You asked:\n> Which printer is it?\nThe one on level 2."],
    'a reply written entirely beneath the quote' => ["On Mon, 2 Nov 2026, Help Desk wrote:\n> Which printer is it?\nThe one on level 2."],
]);

it('has nothing to keep from no text', function (): void {
    expect(QuotedReply::strip(null))->toBeNull();
});
