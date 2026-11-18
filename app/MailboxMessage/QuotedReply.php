<?php

declare(strict_types=1);

namespace App\MailboxMessage;

/**
 * Finds what someone wrote in a reply, apart from the earlier messages their
 * mail program quoted beneath it. Microsoft does this for us; this is for the
 * mail services that do not.
 *
 * It recognises the ways the common mail programs introduce quoted history
 * in English, and a trailing block of ">" lines. When in doubt it keeps
 * the text: the whole message is stored either way.
 */
final class QuotedReply
{
    /**
     * How many lines an Outlook-style header block may spread over.
     */
    private const int HEADER_BLOCK_LINES = 6;

    public static function strip(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $cut = self::cutAt($lines);

        if ($cut === null) {
            return $text;
        }

        $kept = mb_trim(implode("\n", array_slice($lines, 0, $cut)));

        // A reply written entirely beneath or inside the quote: keep it all.
        return $kept === '' ? $text : $kept;
    }

    /**
     * The line the quoted history starts on, if any.
     *
     * @param  list<string>  $lines
     */
    private static function cutAt(array $lines): ?int
    {
        foreach ($lines as $index => $line) {
            $line = mb_trim($line);

            if (preg_match('/^-{2,}\s*(Original|Forwarded) Message\s*-{2,}$/i', $line) === 1) {
                return $index;
            }

            // "On Mon, 2 Nov 2026 at 09:14, Kim Lee <kim@acme.test> wrote:", which long addresses wrap onto a second line.
            if (preg_match('/^On\b.+\bwrote:$/i', $line) === 1
                || (preg_match('/^On\b.+/i', $line) === 1 && preg_match('/\bwrote:$/i', mb_trim($lines[$index + 1] ?? '')) === 1)) {
                return $index;
            }

            if (self::startsHeaderBlock($lines, $index)) {
                // Outlook rules a line above the block; take that with it.
                return $index > 0 && preg_match('/^_{5,}$/', mb_trim($lines[$index - 1])) === 1 ? $index - 1 : $index;
            }
        }

        return self::trailingQuote($lines);
    }

    /**
     * Whether a line starts the "From: / Sent: / To: / Subject:" block that
     * Outlook puts above the message being answered.
     *
     * @param  list<string>  $lines
     */
    private static function startsHeaderBlock(array $lines, int $index): bool
    {
        if (preg_match('/^From:\s*\S/i', mb_trim($lines[$index])) !== 1) {
            return false;
        }

        $block = implode("\n", array_map(mb_trim(...), array_slice($lines, $index + 1, self::HEADER_BLOCK_LINES)));

        return preg_match('/^(Sent|Date):/mi', $block) === 1 && preg_match('/^(To|Subject):/mi', $block) === 1;
    }

    /**
     * Where a block of ">" lines that runs to the end of the message starts.
     *
     * @param  list<string>  $lines
     */
    private static function trailingQuote(array $lines): ?int
    {
        $start = null;

        for ($index = count($lines) - 1; $index >= 0; $index--) {
            $line = mb_trim($lines[$index]);

            if ($line === '') {
                continue;
            }

            if (! str_starts_with($line, '>')) {
                break;
            }

            $start = $index;
        }

        return $start;
    }
}
