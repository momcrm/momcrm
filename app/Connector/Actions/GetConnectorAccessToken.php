<?php

declare(strict_types=1);

namespace App\Connector\Actions;

use App\Connector\Connector;
use App\Connector\ConnectorEloquentModel;
use App\Connector\ConnectorStatusEnum;
use App\Connector\Exceptions\CouldNotReachConnector;
use App\Connector\Sdk\ConnectsBySignIn;
use App\Shared\Types;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * A token that lets us act as a connected account for the next hour or so. It
 * is kept in the cache until shortly before it expires; after that the
 * connector's stored refresh token is exchanged for a new one.
 */
final readonly class GetConnectorAccessToken
{
    /**
     * Stop using a token this long before the service says it expires.
     */
    private const int EXPIRY_MARGIN_IN_SECONDS = 120;

    public function __construct(
        private Connector $connector,
    ) {}

    /**
     * @throws CouldNotReachConnector
     */
    public function __invoke(): string
    {
        $cached = Cache::get(self::cacheKey($this->connector));

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $type = $this->connector->connectorType();
        $company = $type->company();

        $model = ConnectorEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->connector->getId()->toString());

        $refreshToken = $model?->credentials[ConnectorEloquentModel::REFRESH_TOKEN] ?? null;

        if ($model === null || $model->status !== ConnectorStatusEnum::Connected->value || ! is_string($refreshToken) || $refreshToken === '') {
            throw CouldNotReachConnector::needsReconnect($company);
        }

        if (! $type instanceof ConnectsBySignIn) {
            throw CouldNotReachConnector::needsReconnect($company);
        }

        $driver = $type->signInDriver();

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(20)
                ->post($type->tokenUrl(), [
                    'client_id' => config()->string("services.{$driver}.client_id"),
                    'client_secret' => config()->string("services.{$driver}.client_secret"),
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                ]);
        } catch (ConnectionException $exception) {
            throw CouldNotReachConnector::unavailable($company, $exception);
        }

        /*
         * Only a refused sign-in is the connector's problem. Any other error,
         * such as our own client secret having expired, is ours: reconnecting
         * would not fix it, so the connector is left as it is.
         */
        if (in_array($response->json('error'), ['invalid_grant', 'interaction_required'], true)) {
            throw MarkConnectorAsNeedingReconnect::fromConnector($this->connector);
        }

        $accessToken = $response->json('access_token');

        if (! $response->successful() || ! is_string($accessToken) || $accessToken === '') {
            throw CouldNotReachConnector::unavailable($company);
        }

        $replacement = $response->json('refresh_token');

        if (is_string($replacement) && $replacement !== '' && $replacement !== $refreshToken) {
            $model->forceFill([
                ConnectorEloquentModel::CREDENTIALS => [...$model->credentials, ConnectorEloquentModel::REFRESH_TOKEN => $replacement],
            ])->saveQuietly();
        }

        self::remember($this->connector, $accessToken, Types::intOrZero($response->json('expires_in')));

        return $accessToken;
    }

    /**
     * @throws CouldNotReachConnector
     */
    public static function forConnector(Connector $connector): string
    {
        return new self($connector)->handle();
    }

    /**
     * Keeps a token the service just issued, so the next request need not ask again.
     */
    public static function remember(Connector $connector, string $accessToken, int $expiresInSeconds): void
    {
        $seconds = $expiresInSeconds - self::EXPIRY_MARGIN_IN_SECONDS;

        if ($seconds > 0) {
            Cache::put(self::cacheKey($connector), $accessToken, $seconds);
        }
    }

    public static function forget(Connector $connector): void
    {
        Cache::forget(self::cacheKey($connector));
    }

    /**
     * @throws CouldNotReachConnector
     */
    public function handle(): string
    {
        return $this->__invoke();
    }

    /**
     * @throws CouldNotReachConnector
     */
    public function execute(): string
    {
        return $this->__invoke();
    }

    private static function cacheKey(Connector $connector): string
    {
        return "connector.{$connector->getId()->toString()}.access-token";
    }
}
