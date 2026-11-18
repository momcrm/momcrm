<?php

declare(strict_types=1);

namespace App\Mailbox;

use App\Mailbox\Actions\GetMailboxAccessToken;
use App\Mailbox\Exceptions\CouldNotReachMailbox;
use App\Shared\Types;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Microsoft Graph, for one mailbox. A person's own mailbox is reached as "me";
 * a shared mailbox is reached by its address, through the person who has
 * access to it.
 */
final readonly class MicrosoftGraph
{
    /**
     * What a person agrees to when connecting their own mailbox.
     */
    public const array SCOPES = ['offline_access', 'User.Read', 'Mail.ReadWrite', 'Mail.Send'];

    /**
     * What a person agrees to when connecting a shared mailbox they can open.
     */
    public const array SHARED_SCOPES = ['offline_access', 'User.Read', 'Mail.ReadWrite.Shared', 'Mail.Send.Shared'];

    private const string BASE_URL = 'https://graph.microsoft.com/v1.0';

    private const int PAGE_SIZE = 100;

    private function __construct(
        private string $accessToken,
        private ?string $sharedAddress,
    ) {}

    /**
     * For a mailbox that is not saved yet, with the token Microsoft just gave.
     */
    public static function withToken(string $accessToken, ?string $sharedAddress = null): self
    {
        return new self($accessToken, $sharedAddress);
    }

    /**
     * @throws CouldNotReachMailbox
     */
    public static function forMailbox(Mailbox $mailbox): self
    {
        return new self(
            GetMailboxAccessToken::forMailbox($mailbox),
            $mailbox->getIsShared()->toBoolean() ? $mailbox->getAddress()->toString() : null,
        );
    }

    /**
     * The mailbox's inbox folder. Asking for it is how we find out whether the
     * mailbox exists and the signed-in person may open it.
     *
     * @return array<string, mixed>
     *
     * @throws CouldNotReachMailbox
     */
    public function inbox(): array
    {
        return self::json($this->get($this->path('mailFolders/inbox'), ['$select' => 'id,displayName']));
    }

    /**
     * The inbox's messages received at or after a time, oldest first: just
     * their ids and when they arrived, and no more than the limit.
     *
     * @return list<array{id: string, received_at: CarbonImmutable}>
     *
     * @throws CouldNotReachMailbox
     */
    public function inboxMessagesSince(CarbonImmutable $since, int $limit): array
    {
        $messages = [];
        $url = $this->path('mailFolders/inbox/messages');
        $query = [
            '$filter' => 'receivedDateTime ge '.$since->utc()->format('Y-m-d\TH:i:s\Z'),
            '$orderby' => 'receivedDateTime asc',
            '$select' => 'id,receivedDateTime',
            '$top' => (string) min($limit, self::PAGE_SIZE),
        ];

        while ($url !== null && count($messages) < $limit) {
            $page = self::json($this->get($url, $query));

            foreach (is_array($page['value'] ?? null) ? $page['value'] : [] as $message) {
                $id = is_array($message) ? ($message['id'] ?? null) : null;
                $receivedAt = is_array($message) ? ($message['receivedDateTime'] ?? null) : null;

                if (is_string($id) && is_string($receivedAt) && count($messages) < $limit) {
                    $messages[] = ['id' => $id, 'received_at' => CarbonImmutable::parse($receivedAt)->utc()];
                }
            }

            // The next page's address already carries the query.
            $next = $page['@odata.nextLink'] ?? null;
            $url = is_string($next) && $next !== '' ? $next : null;
            $query = [];
        }

        return $messages;
    }

    /**
     * One message with its headers, and its body as plain text. Null when it
     * is no longer in the mailbox.
     *
     * @return array<string, mixed>|null
     *
     * @throws CouldNotReachMailbox
     */
    public function message(string $id): ?array
    {
        $response = $this->get(
            $this->path('messages/'.rawurlencode($id)),
            ['$select' => 'id,subject,from,toRecipients,receivedDateTime,internetMessageId,isDraft,hasAttachments,body,uniqueBody,internetMessageHeaders'],
            ['Prefer' => 'IdType="ImmutableId", outlook.body-content-type="text"'],
        );

        return $response->notFound() ? null : self::json($response);
    }

    /**
     * A draft reply to a message in the mailbox, which Microsoft threads
     * under it. Null when that message is no longer in the mailbox.
     *
     * @return array<string, mixed>|null
     *
     * @throws CouldNotReachMailbox
     */
    public function createReplyDraft(string $messageId): ?array
    {
        $response = $this->write('post', $this->path('messages/'.rawurlencode($messageId).'/createReply'));

        return $response->notFound() ? null : self::json($response);
    }

    /**
     * A new draft in the mailbox.
     *
     * @param  array<string, mixed>  $message
     * @return array<string, mixed>
     *
     * @throws CouldNotReachMailbox
     */
    public function createDraft(array $message): array
    {
        return self::json($this->write('post', $this->path('messages'), $message));
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     *
     * @throws CouldNotReachMailbox
     */
    public function updateDraft(string $draftId, array $changes): array
    {
        return self::json($this->write('patch', $this->path('messages/'.rawurlencode($draftId)), $changes));
    }

    /**
     * Sends a draft, which Microsoft then keeps in Sent Items.
     *
     * @throws CouldNotReachMailbox
     */
    public function sendDraft(string $draftId): void
    {
        self::json($this->write('post', $this->path('messages/'.rawurlencode($draftId).'/send')));
    }

    /**
     * A request that changes the mailbox. It is never retried here: sending
     * one twice could send a customer the same email twice.
     *
     * @param  array<string, mixed>  $body
     *
     * @throws CouldNotReachMailbox
     */
    private function write(string $method, string $url, array $body = []): Response
    {
        try {
            return $this->request()->send(mb_strtoupper($method), $url, $body === [] ? [] : ['json' => $body]);
        } catch (ConnectionException $exception) {
            throw CouldNotReachMailbox::unavailable($exception);
        }
    }

    /**
     * @param  array<string, string>  $query
     * @param  array<string, string>  $headers
     *
     * @throws CouldNotReachMailbox
     */
    private function get(string $url, array $query = [], array $headers = []): Response
    {
        try {
            $request = $this->request()
                ->withHeaders($headers)
                ->retry([200, 1000], when: self::isTransient(...), throw: false);

            // Passing an empty query would strip the one a next-page address carries.
            return $query === [] ? $request->get($url) : $request->get($url, $query);
        } catch (ConnectionException $exception) {
            throw CouldNotReachMailbox::unavailable($exception);
        }
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(self::BASE_URL)
            ->withToken($this->accessToken)
            ->withHeaders(['Prefer' => 'IdType="ImmutableId"'])
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(20);
    }

    private function path(string $path): string
    {
        $mailbox = $this->sharedAddress === null ? 'me' : 'users/'.rawurlencode($this->sharedAddress);

        return "{$mailbox}/{$path}";
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
     * @throws CouldNotReachMailbox
     */
    private static function json(Response $response): array
    {
        if ($response->successful()) {
            return Types::stringKeyed($response->json() ?? []);
        }

        if ($response->serverError() || $response->status() === 429) {
            throw CouldNotReachMailbox::unavailable();
        }

        if ($response->status() === 401) {
            throw CouldNotReachMailbox::needsReconnect();
        }

        $message = $response->json('error.message');

        throw CouldNotReachMailbox::refused(is_string($message) && $message !== ''
            ? $message
            : 'Microsoft refused the request.');
    }
}
