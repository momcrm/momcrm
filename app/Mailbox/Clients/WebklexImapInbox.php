<?php

declare(strict_types=1);

namespace App\Mailbox\Clients;

use App\Connector\ConnectorTypeEnum;
use App\Connector\Exceptions\CouldNotReachConnector;
use App\Mailbox\Contracts\ImapInbox;
use Carbon\CarbonImmutable;
use Throwable;
use Webklex\PHPIMAP\Attachment;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Connection\Protocols\ImapProtocol;
use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\Exceptions\MessageHeaderFetchingException;
use Webklex\PHPIMAP\Exceptions\MessageNotFoundException;
use Webklex\PHPIMAP\Query\WhereQuery;

/**
 * An inbox on a real IMAP server, through the webklex/php-imap library. This
 * is the only class that knows the library; it is kept thin because only a
 * real server exercises it.
 */
final readonly class WebklexImapInbox implements ImapInbox
{
    private const string FOLDER = 'INBOX';

    /**
     * How many messages one request asks the server about.
     */
    private const int UIDS_PER_REQUEST = 200;

    public function __construct(
        private Client $client,
        private int $uidValidity,
    ) {}

    public function uidValidity(): int
    {
        return $this->uidValidity;
    }

    public function since(CarbonImmutable $since): array
    {
        try {
            $this->client->openFolder(self::FOLDER);

            $uids = array_values(array_filter(
                (array) $this->connection()->search(['SINCE '.$since->utc()->format('j-M-Y')])->validatedData(),
                fn (mixed $uid): bool => is_numeric($uid),
            ));

            $dates = $this->receivedAt(array_map(intval(...), $uids));
        } catch (Throwable $exception) {
            throw self::failure($exception);
        }

        $listed = [];

        foreach ($dates as $uid => $receivedAt) {
            $listed[] = ['uid' => $uid, 'received_at' => $receivedAt];
        }

        usort($listed, fn (array $a, array $b): int => $a['uid'] <=> $b['uid']);

        return $listed;
    }

    public function message(int $uid): ?array
    {
        try {
            $this->client->openFolder(self::FOLDER);

            // Reading must never mark the customer's mail as read.
            $message = new WhereQuery($this->client)->leaveUnread()->getMessageByUid($uid);

            $receivedAt = $this->receivedAt([$uid])[$uid] ?? null;
        } catch (MessageNotFoundException|MessageHeaderFetchingException) {
            // The server had nothing under that number: the message has gone.
            return null;
        } catch (Throwable $exception) {
            throw self::failure($exception);
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

        return [
            'headers' => self::headers($message->getHeader()->raw ?? ''),
            'text' => $message->hasTextBody() ? $message->getTextBody() : null,
            'html' => $message->hasHTMLBody() ? $message->getHTMLBody() : null,
            'received_at' => $receivedAt,
            'is_draft' => $message->hasFlag('draft'),
            'attachments' => $attachments,
        ];
    }

    /**
     * When the server received each of these messages, by message number.
     *
     * The answer is read as the server wrote it. The library's own reading
     * breaks a date apart when it is the last thing on the line, which is
     * where servers usually put it.
     *
     * @param  list<int>  $uids
     * @return array<int, CarbonImmutable>
     *
     * @throws CouldNotReachConnector
     */
    private function receivedAt(array $uids): array
    {
        $dates = [];

        foreach (array_chunk($uids, self::UIDS_PER_REQUEST) as $chunk) {
            $lines = $this->connection()->requestAndResponse('UID FETCH', [implode(',', $chunk), '(INTERNALDATE)'], true)->data();

            foreach (is_array($lines) ? $lines : [] as $line) {
                if (is_string($line)
                    && preg_match('/\bUID (\d+)\b/i', $line, $uid) === 1
                    && preg_match('/\bINTERNALDATE "([^"]+)"/i', $line, $date) === 1) {
                    $dates[(int) $uid[1]] = CarbonImmutable::parse($date[1])->utc();
                }
            }
        }

        return $dates;
    }

    /**
     * The library's own IMAP connection, which is what can ask the server
     * when a message arrived.
     *
     * @throws CouldNotReachConnector
     */
    private function connection(): ImapProtocol
    {
        $connection = $this->client->getConnection();

        if (! $connection instanceof ImapProtocol) {
            throw CouldNotReachConnector::unavailable(ConnectorTypeEnum::Imap);
        }

        return $connection;
    }

    /**
     * What went wrong, in the terms the rest of the app understands.
     */
    public static function failure(Throwable $exception): CouldNotReachConnector
    {
        if ($exception instanceof CouldNotReachConnector) {
            return $exception;
        }

        return $exception instanceof AuthFailedException
            ? CouldNotReachConnector::needsReconnect(ConnectorTypeEnum::Imap)
            : CouldNotReachConnector::unavailable(ConnectorTypeEnum::Imap, $exception);
    }

    /**
     * A message's headers by lower-cased name, as they were sent: long
     * headers are folded over several lines, and the first of a name wins.
     *
     * @return array<string, string>
     */
    private static function headers(string $raw): array
    {
        $headers = [];
        $unfolded = preg_replace('/\r?\n[ \t]+/', ' ', $raw) ?? $raw;

        foreach (preg_split('/\r?\n/', $unfolded) ?: [] as $line) {
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
