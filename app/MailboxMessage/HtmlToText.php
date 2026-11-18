<?php

declare(strict_types=1);

namespace App\MailboxMessage;

/**
 * An HTML email as plain text, for mail that has no plain-text part. Only the
 * words and their line breaks are kept.
 */
final class HtmlToText
{
    public static function convert(string $html): string
    {
        // What a reader never sees: the head, styles, scripts and comments.
        $text = preg_replace('#<(head|style|script|title)\b[^>]*>.*?</\1\s*>#is', '', $html) ?? $html;
        $text = preg_replace('/<!--.*?-->/s', '', $text) ?? $text;

        // Source line breaks mean nothing in HTML; the tags decide.
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        $text = preg_replace('#<br\s*/?>#i', "\n", $text) ?? $text;
        $text = preg_replace('#<li\b[^>]*>#i', "\n- ", $text) ?? $text;
        $text = preg_replace('#</(p|div|tr|table|ul|ol|blockquote|h[1-6])\s*>#i', "\n\n", $text) ?? $text;
        $text = preg_replace('#</(td|th)\s*>#i', ' ', $text) ?? $text;

        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);

        $lines = array_map(fn (string $line): string => mb_trim($line), explode("\n", $text));
        $text = preg_replace("/\n{3,}/", "\n\n", implode("\n", $lines)) ?? '';

        return mb_trim($text);
    }
}
