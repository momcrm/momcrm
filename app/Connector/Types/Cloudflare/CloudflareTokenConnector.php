<?php

declare(strict_types=1);

namespace App\Connector\Types\Cloudflare;

use App\Shared\Types;
use Mom\Connectors\ConnectedAccount;
use Mom\Connectors\ConnectsByForm;
use Mom\Connectors\CouldNotConnect;
use Mom\Connectors\CouldNotReachConnector;
use Mom\Connectors\Field;

/**
 * A Cloudflare account, connected with an API token that someone creates in
 * Cloudflare and pastes in. This is how an installation that has no OAuth
 * client of its own at Cloudflare connects accounts, and how an account
 * whose admins do not allow signing in to outside apps is connected.
 */
final class CloudflareTokenConnector extends CloudflareAccount implements ConnectsByForm
{
    public const string KEY = 'cloudflare-token';

    public const string API_TOKEN = 'api_token';

    private const string ACCOUNT_ID = 'account_id';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'Cloudflare API token';
    }

    public function description(): string
    {
        return 'A Cloudflare account, connected with an API token instead of by signing in.';
    }

    public function formIntroduction(): string
    {
        return 'MomCRM puts a small Worker in your Cloudflare account that hands it the mail Email Routing receives, and sends replies through Email Sending. It needs an API token with these permissions: Workers Scripts (Edit), Email Sending (Edit), Email Routing Rules (Edit), Email Routing Account Rules (Read), Zone (Read) and Zone Settings (Read).';
    }

    public function fields(): array
    {
        return [
            Field::text(self::ACCOUNT_ID, 'Account ID')
                ->required()
                ->placeholder('32 letters and numbers')
                ->hint('In Cloudflare, on the Overview page of any of your domains.')
                ->rules(['regex:/^[A-Fa-f0-9]{32}$/'])
                ->message('regex', (string) __('An account ID is 32 letters and numbers, with nothing around it.')),
            Field::password(self::API_TOKEN, 'API token')
                ->required()
                ->hint('In Cloudflare, under Manage Account, then Account API Tokens. Include the domains whose mail you want.'),
        ];
    }

    public function accountField(): string
    {
        return self::ACCOUNT_ID;
    }

    /**
     * Checks the token and puts the Worker in the account before anything is
     * saved, so an account that could never hand mail over is never listed.
     * Connecting the same account again replaces the Worker, which is how a
     * moved MomCRM or a changed Worker reaches Cloudflare.
     */
    public function connect(array $values): ConnectedAccount
    {
        $accountId = mb_strtolower(Types::string($values[self::ACCOUNT_ID] ?? ''));
        $token = Types::string($values[self::API_TOKEN] ?? '');
        $api = new CloudflareApi($accountId, $token);

        if (! self::canBeHandedMail()) {
            throw CouldNotConnect::because(self::ACCOUNT_ID, self::whyNoMailCanBeHandedOver());
        }

        try {
            if (! $api->tokenIsActive()) {
                throw CouldNotConnect::because(self::API_TOKEN, (string) __('Cloudflare did not accept that API token.'));
            }

            $zones = $api->zones();
        } catch (CouldNotReachConnector $exception) {
            throw CouldNotConnect::because(self::API_TOKEN, $exception->needsReconnect ? (string) __('Cloudflare did not accept that API token.') : $exception->getMessage());
        }

        if ($zones === []) {
            throw CouldNotConnect::because(self::ACCOUNT_ID, (string) __("That token can't see any domains in this account. Check the account ID, and that the token includes the domains whose mail you want."));
        }

        try {
            self::deployWorker($api, $accountId);
        } catch (CouldNotReachConnector $exception) {
            throw CouldNotConnect::because(self::API_TOKEN, $exception->isTemporary
                ? $exception->getMessage()
                : (string) __('MomCRM could not put its Worker in the account. Check that the token has Workers Scripts (Edit).'));
        }

        return new ConnectedAccount(
            account: $accountId,
            name: $zones[0]['account_name'] ?? self::COMPANY,
            settings: [self::ACCOUNT_ID => $accountId],
            credentials: [self::API_TOKEN => $token],
        );
    }
}
