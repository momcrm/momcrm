<?php

declare(strict_types=1);

namespace App\MailboxMessage;

use Throwable;
use Webklex\PHPIMAP\Attachment;
use Webklex\PHPIMAP\Message;

/**
 * An email read from its source, the way it travels between mail servers:
 * headers, then the text, the HTML and the files, each wrapped and encoded.
 * For the mail services that hand us a whole email and leave reading it to us.
 */
final readonly class RawMail
{
    /**
     * @param  array<string, string>  $headers  by lower-cased name, as they were sent
     * @param  list<array{name: string, content_type: ?string, size: int, is_inline: bool, content: string}>  $attachments
     */
    private function __construct(
        public array $headers,
        public ?string $text,
        public ?string $html,
        public array $attachments,
    ) {}

    /**
     * Null when the source cannot be made sense of as an email.
     */
    public static function read(string $source): ?self
    {
        // Mail servers end lines with a carriage return and a line feed, and an email with no text ends at its headers.
        $source = preg_replace('/\r?\n/', "\r\n", $source) ?? $source;
        $source = str_contains($source, "\r\n\r\n") ? $source : mb_rtrim($source, "\r\n")."\r\n\r\n";

        try {
            $message = Message::fromString($source);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }

        $attachments = [];

        foreach ($message->getAttachments() as $attachment) {
            if (! $attachment instanceof Attachment) {
                continue;
            }

            $content = $attachment->getAttributes()['content'] ?? null;
            $name = $attachment->getAttributes()['name'] ?? null;
            $contentType = $attachment->getAttributes()['content_type'] ?? null;
            $disposition = $attachment->getAttributes()['disposition'] ?? null;

            $attachments[] = [
                'name' => is_string($name) ? $name : '',
                'content_type' => is_string($contentType) ? $contentType : null,
                'size' => is_string($content) ? strlen($content) : 0,
                'is_inline' => is_string($disposition) && mb_strtolower($disposition) === 'inline',
                'content' => is_string($content) ? $content : '',
            ];
        }

        return new self(
            self::headers((string) mb_strstr($source, "\r\n\r\n", true)),
            $message->hasTextBody() ? $message->getTextBody() : null,
            $message->hasHTMLBody() ? $message->getHTMLBody() : null,
            $attachments,
        );
    }

    /**
     * The email as plain text: its text, or its HTML turned into text when
     * it has no other.
     */
    public function bodyText(): ?string
    {
        return $this->text ?? ($this->html === null ? null : HtmlToText::convert($this->html));
    }

    /**
     * Headers by lower-cased name: long headers are folded over several
     * lines, and the first of a name wins.
     *
     * @return array<string, string>
     */
    private static function headers(string $raw): array
    {
        $headers = [];
        $unfolded = preg_replace('/\r\n[ \t]+/', ' ', $raw) ?? $raw;

        foreach (explode("\r\n", $unfolded) as $line) {
            $colon = mb_strpos($line, ':');

            if ($colon === false) {
                continue;
            }

            $name = mb_strtolower(mb_trim(mb_substr($line, 0, $colon)));

            if ($name !== '' && ! isset($headers[$name])) {
                $headers[$name] = mb_trim(mb_substr($line, $colon + 1));
            }
        }

        return $headers;
    }
}
