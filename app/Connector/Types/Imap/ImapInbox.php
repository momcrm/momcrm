<?php

declare(strict_types=1);

namespace App\Connector\Types\Imap;

use App\Connector\Exceptions\CouldNotReachConnector;
use Carbon\CarbonImmutable;

/**
 * The inbox of a mailbox on an IMAP server, signed in and open. It gives
 * plain values, so nothing outside the adapter depends on an IMAP library.
 */
interface ImapInbox
{
    /**
     * The number the server gives this inbox. Message numbers only mean
     * something together with it: when it changes, the inbox was rebuilt.
     */
    public function uidValidity(): int;

    /**
     * The messages that arrived on or after the day of a time, oldest first:
     * their numbers and when the server received them. IMAP can only search
     * by day, so some may be from earlier that day.
     *
     * @return list<array{uid: int, received_at: CarbonImmutable}>
     *
     * @throws CouldNotReachConnector
     */
    public function since(CarbonImmutable $since): array;

    /**
     * One message: its headers by lower-cased name, its text and HTML when
     * it has them, and its files with their contents. Null when it has gone.
     *
     * @return array{headers: array<string, string>, text: ?string, html: ?string, received_at: ?CarbonImmutable, is_draft: bool, attachments: list<array{name: string, content_type: ?string, size: int, is_inline: bool, content: string}>}|null
     *
     * @throws CouldNotReachConnector
     */
    public function message(int $uid): ?array;
}
