<?php

declare(strict_types=1);

namespace App\Mailbox\Actions;

use App\Mailbox\Exceptions\CouldNotReachMailbox;
use App\Mailbox\Mailbox;
use App\Mailbox\MailboxEloquentModel;
use App\Mailbox\MailboxStatusEnum;
use App\Mailbox\Properties\LastError;
use App\Mailbox\Properties\Status;
use App\Shared\Types;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * A token that lets us into a mailbox for the next hour or so. It is kept in
 * the cache until shortly before it expires; after that the mailbox's stored
 * refresh token is exchanged for a new one.
 */
final readonly class GetMailboxAccessToken
{
    /**
     * Stop using a token this long before Microsoft says it expires.
     */
    private const int EXPIRY_MARGIN_IN_SECONDS = 120;

    public function __construct(
        private Mailbox $mailbox,
    ) {}

    /**
     * @throws CouldNotReachMailbox
     */
    public function __invoke(): string
    {
        $cached = Cache::get(self::cacheKey($this->mailbox));

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $model = MailboxEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->mailbox->getId()->toString());

        if ($model === null || $model->status !== MailboxStatusEnum::Connected->value) {
            throw CouldNotReachMailbox::needsReconnect();
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(20)
                ->post(self::tokenUrl(), [
                    'client_id' => config()->string('services.microsoft.client_id'),
                    'client_secret' => config()->string('services.microsoft.client_secret'),
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $model->refresh_token,
                ]);
        } catch (ConnectionException $exception) {
            throw CouldNotReachMailbox::unavailable($exception);
        }

        /*
         * Only a refused sign-in is the mailbox's problem. Any other error,
         * such as our own client secret having expired, is ours: reconnecting
         * would not fix it, so the mailbox is left as it is.
         */
        if (in_array($response->json('error'), ['invalid_grant', 'interaction_required'], true)) {
            $exception = CouldNotReachMailbox::needsReconnect();

            $model->forceFill([
                Status::getDatabaseTableColumnName() => MailboxStatusEnum::NeedsReconnect->value,
                LastError::getDatabaseTableColumnName() => $exception->getMessage(),
            ])->save();

            throw $exception;
        }

        $accessToken = $response->json('access_token');

        if (! $response->successful() || ! is_string($accessToken) || $accessToken === '') {
            throw CouldNotReachMailbox::unavailable();
        }

        $refreshToken = $response->json('refresh_token');

        if (is_string($refreshToken) && $refreshToken !== '' && $refreshToken !== $model->refresh_token) {
            $model->forceFill([MailboxEloquentModel::REFRESH_TOKEN => $refreshToken])->saveQuietly();
        }

        self::remember($this->mailbox, $accessToken, Types::intOrZero($response->json('expires_in')));

        return $accessToken;
    }

    /**
     * @throws CouldNotReachMailbox
     */
    public static function forMailbox(Mailbox $mailbox): string
    {
        return new self($mailbox)->handle();
    }

    /**
     * Keeps a token Microsoft just issued, so the next request need not ask again.
     */
    public static function remember(Mailbox $mailbox, string $accessToken, int $expiresInSeconds): void
    {
        $seconds = $expiresInSeconds - self::EXPIRY_MARGIN_IN_SECONDS;

        if ($seconds > 0) {
            Cache::put(self::cacheKey($mailbox), $accessToken, $seconds);
        }
    }

    public static function forget(Mailbox $mailbox): void
    {
        Cache::forget(self::cacheKey($mailbox));
    }

    /**
     * @throws CouldNotReachMailbox
     */
    public function handle(): string
    {
        return $this->__invoke();
    }

    /**
     * @throws CouldNotReachMailbox
     */
    public function execute(): string
    {
        return $this->__invoke();
    }

    private static function cacheKey(Mailbox $mailbox): string
    {
        return "mailbox.{$mailbox->getId()->toString()}.access-token";
    }

    private static function tokenUrl(): string
    {
        return sprintf(
            'https://login.microsoftonline.com/%s/oauth2/v2.0/token',
            config()->string('services.microsoft.tenant', 'common'),
        );
    }
}
