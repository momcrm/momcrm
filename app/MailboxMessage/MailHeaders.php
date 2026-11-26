<?php

declare(strict_types=1);

namespace App\MailboxMessage;

use Carbon\CarbonImmutable;
use Mom\Connectors\Mail\InboundMail;
use Mom\Connectors\Mail\MessageId;

/**
 * Reads an email from its own headers, for the mail services that hand us
 * the headers and the text and leave the rest to us. What the sender wrote
 * is told apart from the quoted history by QuotedReply.
 */
final class MailHeaders
{
    /**
     * @param  array<string, string>  $headers  by lower-cased name
     * @param  ?string  $bodyHtml  the email as its sender laid it out, when it came with HTML
     */
    public static function toInboundMail(array $headers, ?string $externalId, ?string $bodyText, ?CarbonImmutable $receivedAt, bool $hasAttachments = false, ?string $bodyHtml = null): InboundMail
    {
        $headers = array_map(self::decode(...), $headers);
        $from = self::sender($headers['from'] ?? '');
        $bodyText = self::text($bodyText);

        return new InboundMail(
            externalId: $externalId,
            messageId: MessageId::normalize($headers['message-id'] ?? null),
            inReplyTo: MessageId::normalize($headers['in-reply-to'] ?? null),
            references: MessageId::all($headers['references'] ?? null),
            fromAddress: $from['address'],
            fromName: $from['name'],
            to: self::addressesIn($headers['to'] ?? ''),
            subject: self::text($headers['subject'] ?? null),
            bodyText: $bodyText,
            replyText: QuotedReply::strip($bodyText),
            receivedAt: $receivedAt?->utc(),
            hasAttachments: $hasAttachments,
            bodyHtml: self::text($bodyHtml) === null ? null : $bodyHtml,
        );
    }

    /**
     * A header as people read it: mail programs encode anything that is not
     * plain ASCII, such as "=?UTF-8?B?…?=".
     */
    private static function decode(string $value): string
    {
        $decoded = iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');

        return $decoded === false ? $value : $decoded;
    }

    /**
     * The name and address in a From header, such as "Kim Lee <kim@acme.test>".
     *
     * @return array{name: ?string, address: ?string}
     */
    private static function sender(string $header): array
    {
        if (preg_match('/^(.*)<([^<>\s]+@[^<>\s]+)>\s*$/', $header, $matches) === 1) {
            return ['name' => self::text(mb_trim($matches[1], " \n\r\t\v\0\"'")), 'address' => mb_strtolower(mb_trim($matches[2]))];
        }

        return ['name' => null, 'address' => self::addressesIn($header)[0] ?? null];
    }

    /**
     * Every email address in a header, in lower case.
     *
     * @return list<string>
     */
    private static function addressesIn(string $header): array
    {
        preg_match_all('/[^\s<>,;:"\'()]+@[^\s<>,;:"\'()]+/', $header, $matches);

        return array_values(array_unique(array_map(mb_strtolower(...), $matches[0])));
    }

    private static function text(?string $value): ?string
    {
        $value = mb_trim($value ?? '');

        return $value === '' ? null : $value;
    }
}
