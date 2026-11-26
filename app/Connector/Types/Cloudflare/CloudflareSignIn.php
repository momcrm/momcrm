<?php

declare(strict_types=1);

namespace App\Connector\Types\Cloudflare;

use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;
use RuntimeException;

/**
 * Signing in at Cloudflare to connect an account (OAuth 2, with the code
 * exchange protected by PKCE).
 *
 * What is connected is a Cloudflare account, not the person who signed in:
 * the person picks the account as they agree, and that account is who the
 * sign-in is for. Its id is what the rest of the app knows it by, so it is
 * given as the "account" the sign-in connects.
 */
final class CloudflareSignIn extends AbstractProvider
{
    public const string TOKEN_URL = 'https://dash.cloudflare.com/oauth2/token';

    private const string AUTHORIZE_URL = 'https://dash.cloudflare.com/oauth2/auth';

    private const string API_URL = 'https://api.cloudflare.com/client/v4';

    protected $scopeSeparator = ' ';

    protected $usesPKCE = true;

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(self::AUTHORIZE_URL, $state);
    }

    protected function getTokenUrl(): string
    {
        return self::TOKEN_URL;
    }

    /**
     * The account the sign-in gave access to. When the person picked more
     * than one, it is the first Cloudflare lists.
     *
     * @return array{account: string, name: ?string}
     */
    protected function getUserByToken($token): array
    {
        $account = $this->firstOf($token, 'accounts');

        // A sign-in that may not list accounts can still say whose domains it sees.
        $account ??= $this->firstOf($token, 'zones')['account'] ?? null;

        if (! is_array($account) || ! is_string($account['id'] ?? null) || $account['id'] === '') {
            throw new RuntimeException('The Cloudflare sign-in gave access to no account.');
        }

        return ['account' => $account['id'], 'name' => is_string($account['name'] ?? null) ? $account['name'] : null];
    }

    /**
     * @param  array<array-key, mixed>  $user
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map(['id' => $user['account'] ?? null, 'name' => $user['name'] ?? null]);
    }

    /**
     * The first item of a listing; null when there is none or it may not be listed.
     *
     * @return array<array-key, mixed>|null
     */
    private function firstOf(string $token, string $listing): ?array
    {
        $first = Http::withToken($token)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(20)
            ->get(self::API_URL."/{$listing}", ['per_page' => 5])
            ->json('result.0');

        return is_array($first) ? $first : null;
    }
}
