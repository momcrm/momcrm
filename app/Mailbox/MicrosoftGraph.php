<?php

declare(strict_types=1);

namespace App\Mailbox;

use App\Mailbox\Actions\GetMailboxAccessToken;
use App\Mailbox\Exceptions\CouldNotReachMailbox;
use App\Shared\Types;
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
        return $this->get('mailFolders/inbox', ['$select' => 'id,displayName']);
    }

    /**
     * @param  array<string, string>  $query
     * @return array<string, mixed>
     *
     * @throws CouldNotReachMailbox
     */
    private function get(string $path, array $query = []): array
    {
        try {
            $response = $this->request()
                ->retry([200, 1000], when: self::isTransient(...), throw: false)
                ->get($this->path($path), $query);
        } catch (ConnectionException $exception) {
            throw CouldNotReachMailbox::unavailable($exception);
        }

        return self::json($response);
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
