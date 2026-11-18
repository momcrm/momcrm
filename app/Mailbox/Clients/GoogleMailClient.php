<?php

declare(strict_types=1);

namespace App\Mailbox\Clients;

use App\Connector\Actions\GetConnectorAccessToken;
use App\Connector\Connector;
use App\Connector\ConnectorTypeEnum;
use App\Connector\Exceptions\CouldNotReachConnector;
use App\Mailbox\Contracts\MailClient;
use App\Mailbox\OutgoingMail;
use App\Mailbox\SentMail;
use App\MailboxMessage\HtmlToText;
use App\MailboxMessage\MailboxMessage;
use App\MailboxMessage\Properties\InternetMessageId;
use App\Shared\Types;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mime\Address;
use Throwable;

/**
 * A Gmail or Google Workspace mailbox, through the Gmail API. The mailbox is
 * always the connected account's own: Google has no shared mailboxes to open
 * through someone else's sign-in.
 */
final class GoogleMailClient implements MailClient
{
    private const string BASE_URL = 'https://gmail.googleapis.com/gmail/v1/users/me';

    private const int PAGE_SIZE = 500;

    /**
     * How many pages of ids one listing reads at most. Gmail lists newest
     * first, so finding the oldest means reading to the end.
     */
    private const int MAX_PAGES = 10;

    /**
     * Messages already fetched by this client, so listing a message and then
     * taking it in asks Gmail for it once.
     *
     * @var array<string, array<string, mixed>|null>
     */
    private array $fetched = [];

    private function __construct(
        private readonly string $accessToken,
        private readonly string $address,
    ) {}

    /**
     * @throws CouldNotReachConnector
     */
    public static function forConnector(Connector $connector): self
    {
        return new self(GetConnectorAccessToken::forConnector($connector), $connector->getAccount()->toString());
    }

    public function checkInbox(): void
    {
        self::json($this->get('labels/INBOX'));
    }

    /**
     * Gmail lists only ids, newest first, so the oldest are picked from the
     * end of the list and each is fetched to learn when it arrived.
     */
    public function inboxMessagesSince(CarbonImmutable $since, int $limit): array
    {
        $ids = [];
        $pageToken = null;
        $pages = 0;

        do {
            $page = self::json($this->get('messages', [
                'labelIds' => 'INBOX',
                'q' => 'after:'.$since->getTimestamp(),
                'maxResults' => (string) self::PAGE_SIZE,
                ...($pageToken === null ? [] : ['pageToken' => $pageToken]),
            ]));

            foreach (is_array($page['messages'] ?? null) ? $page['messages'] : [] as $listed) {
                $id = is_array($listed) ? ($listed['id'] ?? null) : null;

                if (is_string($id)) {
                    $ids[] = $id;
                }
            }

            $next = $page['nextPageToken'] ?? null;
            $pageToken = is_string($next) && $next !== '' ? $next : null;
            $pages++;
        } while ($pageToken !== null && $pages < self::MAX_PAGES);

        $messages = [];

        foreach (array_slice($ids, -$limit) as $id) {
            $receivedAt = self::receivedAt($this->raw($id) ?? []);

            if ($receivedAt !== null && $receivedAt->greaterThanOrEqualTo($since)) {
                $messages[] = ['id' => $id, 'received_at' => $receivedAt];
            }
        }

        usort($messages, fn (array $a, array $b): int => $a['received_at'] <=> $b['received_at']);

        return $messages;
    }

    public function message(string $id): ?MailboxMessage
    {
        $raw = $this->raw($id);

        if ($raw === null || in_array('DRAFT', is_array($raw['labelIds'] ?? null) ? $raw['labelIds'] : [], true)) {
            return null;
        }

        $payload = self::map($raw['payload'] ?? []);

        return MailboxMessage::fromHeaders(self::headers($payload), $id, $this->bodyText($id, $payload), self::receivedAt($raw))
            ->setHasAttachments(self::fileParts($payload) !== []);
    }

    public function attachments(string $messageId): array
    {
        $attachments = [];

        foreach (self::fileParts(self::map(($this->raw($messageId) ?? [])['payload'] ?? [])) as $part) {
            $body = self::map($part['body'] ?? []);
            $attachmentId = $body['attachmentId'] ?? null;
            $disposition = self::headers($part)['content-disposition'] ?? '';

            $attachments[] = [
                'id' => is_string($attachmentId) && $attachmentId !== '' ? $attachmentId : 'part:'.self::str($part['partId'] ?? null),
                'name' => self::str($part['filename'] ?? null),
                'content_type' => is_string($part['mimeType'] ?? null) ? $part['mimeType'] : null,
                'size' => is_numeric($body['size'] ?? null) ? (int) $body['size'] : 0,
                'is_file' => true,
                'is_inline' => str_starts_with(mb_strtolower(mb_trim($disposition)), 'inline'),
            ];
        }

        return $attachments;
    }

    /**
     * Small files come inside the message itself, under their part's number;
     * the rest are fetched by the id Gmail gave them.
     */
    public function attachmentContents(string $messageId, string $attachmentId): string
    {
        if (str_starts_with($attachmentId, 'part:')) {
            foreach (self::parts(self::map(($this->raw($messageId) ?? [])['payload'] ?? [])) as $part) {
                if ('part:'.self::str($part['partId'] ?? null) === $attachmentId) {
                    return self::decode(self::map($part['body'] ?? [])['data'] ?? null);
                }
            }

            throw CouldNotReachConnector::refused((string) __('That attachment is no longer on the message.'));
        }

        $attachment = self::json($this->get('messages/'.rawurlencode($messageId).'/attachments/'.rawurlencode($attachmentId)));

        return self::decode($attachment['data'] ?? null);
    }

    /**
     * Gmail takes a whole message, headers and all. A reply names the message
     * it answers and goes into its conversation; Gmail may give the message
     * an id of its own, so the id is read back once it is sent.
     */
    public function send(OutgoingMail $mail): SentMail
    {
        $email = $mail->toEmail(new Address($this->address));
        $threadId = $mail->answered->isNull() ? null : $this->threadId($mail->answered->getExternalId()->toString());

        $sent = self::json($this->write('messages/send', [
            'raw' => rtrim(strtr(base64_encode($email->toString()), '+/', '-_'), '='),
            ...($threadId === null ? [] : ['threadId' => $threadId]),
        ]));

        $id = is_string($sent['id'] ?? null) ? $sent['id'] : null;

        return new SentMail(
            $id,
            $this->messageIdOf($id) ?? OutgoingMail::messageId($email),
            $email->getSubject(),
        );
    }

    /**
     * The message as Gmail gives it; null when it is no longer in the mailbox.
     *
     * @return array<string, mixed>|null
     *
     * @throws CouldNotReachConnector
     */
    private function raw(string $id): ?array
    {
        if (! array_key_exists($id, $this->fetched)) {
            $response = $this->get('messages/'.rawurlencode($id), ['format' => 'full']);

            $this->fetched[$id] = $response->notFound() ? null : self::json($response);
        }

        return $this->fetched[$id];
    }

    /**
     * The conversation a message in the mailbox belongs to, if it is still there.
     *
     * @throws CouldNotReachConnector
     */
    private function threadId(string $id): ?string
    {
        $response = $this->get('messages/'.rawurlencode($id), ['format' => 'minimal']);

        if ($response->notFound()) {
            return null;
        }

        $threadId = self::json($response)['threadId'] ?? null;

        return is_string($threadId) && $threadId !== '' ? $threadId : null;
    }

    /**
     * The Message-ID a sent message went out with. The message has been sent
     * by now, so failing to read this must not make the send look failed.
     */
    private function messageIdOf(?string $id): ?string
    {
        if ($id === null) {
            return null;
        }

        try {
            $message = self::json($this->get('messages/'.rawurlencode($id), ['format' => 'metadata', 'metadataHeaders' => 'Message-ID']));
        } catch (CouldNotReachConnector) {
            return null;
        }

        return InternetMessageId::normalize(self::headers(self::map($message['payload'] ?? []))['message-id'] ?? null);
    }

    /**
     * The message as plain text: its plain-text part, or its HTML part turned
     * into text when it has no other.
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws CouldNotReachConnector
     */
    private function bodyText(string $messageId, array $payload): ?string
    {
        $html = null;

        foreach (self::parts($payload) as $part) {
            if (self::str($part['filename'] ?? null) !== '') {
                continue;
            }

            $type = mb_strtolower(self::str($part['mimeType'] ?? null));

            if ($type === 'text/plain') {
                return $this->partText($messageId, $part);
            }

            if ($type === 'text/html') {
                $html ??= $this->partText($messageId, $part);
            }
        }

        return $html === null ? null : HtmlToText::convert($html);
    }

    /**
     * A text part's contents in UTF-8. A long body is kept apart from the
     * message, like an attachment.
     *
     * @param  array<string, mixed>  $part
     *
     * @throws CouldNotReachConnector
     */
    private function partText(string $messageId, array $part): string
    {
        $body = self::map($part['body'] ?? []);
        $attachmentId = $body['attachmentId'] ?? null;

        $text = is_string($attachmentId) && $attachmentId !== ''
            ? $this->attachmentContents($messageId, $attachmentId)
            : self::decode($body['data'] ?? null);

        $charset = preg_match('/charset="?([^";\s]+)/i', self::headers($part)['content-type'] ?? '', $matches) === 1 ? $matches[1] : 'UTF-8';

        if (mb_strtoupper($charset) !== 'UTF-8' && in_array(mb_strtoupper($charset), array_map(mb_strtoupper(...), mb_list_encodings()), true)) {
            $converted = mb_convert_encoding($text, 'UTF-8', $charset);

            return $converted === false ? $text : $converted;
        }

        return $text;
    }

    /**
     * A message part and every part inside it, outermost first.
     *
     * @param  array<string, mixed>  $part
     * @return list<array<string, mixed>>
     */
    private static function parts(array $part): array
    {
        $parts = [$part];

        foreach (is_array($part['parts'] ?? null) ? $part['parts'] : [] as $child) {
            $parts = [...$parts, ...self::parts(self::map($child))];
        }

        return $parts;
    }

    /**
     * The parts that are files someone attached or placed in the message.
     *
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private static function fileParts(array $payload): array
    {
        return array_values(array_filter(
            self::parts($payload),
            fn (array $part): bool => self::str($part['filename'] ?? null) !== '',
        ));
    }

    /**
     * A part's headers by lower-cased name; the first of each name wins.
     *
     * @param  array<string, mixed>  $part
     * @return array<string, string>
     */
    private static function headers(array $part): array
    {
        $headers = [];

        foreach (is_array($part['headers'] ?? null) ? $part['headers'] : [] as $header) {
            $name = is_array($header) ? ($header['name'] ?? null) : null;
            $value = is_array($header) ? ($header['value'] ?? null) : null;

            if (is_string($name) && is_string($value) && ! isset($headers[mb_strtolower($name)])) {
                $headers[mb_strtolower($name)] = $value;
            }
        }

        return $headers;
    }

    /**
     * When Gmail received the message, which it gives in milliseconds.
     *
     * @param  array<string, mixed>  $raw
     */
    private static function receivedAt(array $raw): ?CarbonImmutable
    {
        $milliseconds = $raw['internalDate'] ?? null;

        return is_numeric($milliseconds) ? CarbonImmutable::createFromTimestampMsUTC((int) $milliseconds) : null;
    }

    /**
     * An object Gmail gives, or an empty one when it is absent.
     *
     * @return array<string, mixed>
     */
    private static function map(mixed $value): array
    {
        return is_array($value) ? Types::stringKeyed($value) : [];
    }

    /**
     * A field Gmail gives as text, or nothing when it is absent.
     */
    private static function str(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }

    /**
     * Gmail hands contents over in URL-safe base64.
     */
    private static function decode(mixed $data): string
    {
        if (! is_string($data)) {
            return '';
        }

        $decoded = base64_decode(strtr($data, '-_', '+/'), true);

        return $decoded === false ? '' : $decoded;
    }

    /**
     * A request that changes the mailbox. It is never retried here: sending
     * one twice could send a customer the same email twice.
     *
     * @param  array<string, mixed>  $body
     *
     * @throws CouldNotReachConnector
     */
    private function write(string $path, array $body): Response
    {
        try {
            return $this->request()->post($path, $body);
        } catch (ConnectionException $exception) {
            throw CouldNotReachConnector::unavailable(ConnectorTypeEnum::Google, $exception);
        }
    }

    /**
     * @param  array<string, string>  $query
     *
     * @throws CouldNotReachConnector
     */
    private function get(string $path, array $query = []): Response
    {
        try {
            return $this->request()
                ->retry([200, 1000], when: self::isTransient(...), throw: false)
                ->get($path, $query);
        } catch (ConnectionException $exception) {
            throw CouldNotReachConnector::unavailable(ConnectorTypeEnum::Google, $exception);
        }
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(self::BASE_URL)
            ->withToken($this->accessToken)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(20);
    }

    /**
     * Failures worth another attempt: no connection, throttling and server errors.
     */
    private static function isTransient(Throwable $exception): bool
    {
        return $exception instanceof ConnectionException
            || ($exception instanceof RequestException
                && ($exception->response->serverError() || $exception->response->status() === 429));
    }

    /**
     * @return array<string, mixed>
     *
     * @throws CouldNotReachConnector
     */
    private static function json(Response $response): array
    {
        if ($response->successful()) {
            return self::map($response->json() ?? []);
        }

        if ($response->serverError() || $response->status() === 429) {
            throw CouldNotReachConnector::unavailable(ConnectorTypeEnum::Google);
        }

        if ($response->status() === 401) {
            throw CouldNotReachConnector::needsReconnect(ConnectorTypeEnum::Google);
        }

        $message = $response->json('error.message');

        throw CouldNotReachConnector::refused(is_string($message) && $message !== ''
            ? $message
            : 'Google refused the request.');
    }
}
