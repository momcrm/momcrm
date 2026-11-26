<?php

declare(strict_types=1);

namespace App\Connector\Types\Cloudflare;

use Mom\Connectors\ConnectsBySignIn;

/**
 * A Cloudflare account, connected by signing in at Cloudflare and agreeing
 * to what MomCRM asks for. Nothing is typed in or pasted: the person picks
 * the account there.
 *
 * It needs this installation's own OAuth client at Cloudflare, with its keys
 * in `services.cloudflare`. An installation without one connects accounts
 * with an API token instead.
 */
final class CloudflareConnector extends CloudflareAccount implements ConnectsBySignIn
{
    public const string KEY = 'cloudflare';

    public const string SIGN_IN_DRIVER = 'cloudflare';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'Cloudflare';
    }

    public function description(): string
    {
        return 'Addresses on your Cloudflare domains, through Email Routing and Email Sending.';
    }

    public function signInDriver(): string
    {
        return self::SIGN_IN_DRIVER;
    }

    public function signInOptions(): array
    {
        return [];
    }

    /**
     * Only what handing mail over and sending replies need: the Worker, the
     * routing rules, sending, and finding a domain and whether its Email
     * Routing is on. Cloudflare lists a domain's rules under the account
     * permission and reads or changes one rule under the other, so both are
     * asked for. The last one is what gives lasting access.
     */
    public function scopes(array $options): array
    {
        return [
            'workers-scripts.edit',
            'email-routing-account-rule.read',
            'email-routing-rule.read',
            'email-routing-rule.write',
            'email-sending.write',
            'zone.read',
            'zone-settings.read',
            'offline_access',
        ];
    }

    public function signInParameters(): array
    {
        return [];
    }

    public function tokenUrl(): string
    {
        return CloudflareSignIn::TOKEN_URL;
    }
}
