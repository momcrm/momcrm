<?php

declare(strict_types=1);

namespace App\Connector\Types\Cloudflare;

use App\Mailbox\Contracts\SetsUpMailboxes;
use App\Shared\Types;
use Illuminate\Support\Facades\Config;
use Mom\Connectors\ConnectedAccount;
use Mom\Connectors\Connection;
use Mom\Connectors\ConnectorType;
use Mom\Connectors\ConnectsByForm;
use Mom\Connectors\CouldNotConnect;
use Mom\Connectors\CouldNotReachConnector;
use Mom\Connectors\Field;
use Mom\Connectors\Mail\MailClient;
use Mom\Connectors\Mail\ProvidesMail;
use Mom\Connectors\PublicHost;

/**
 * A Cloudflare account, connected by an API token. Its Email Routing hands
 * MomCRM the mail that arrives for addresses on the account's domains, and
 * its Email Sending sends the replies.
 *
 * Cloudflare has no inbox to read: it passes each email on as it arrives. So
 * connecting puts a small Worker in the account that passes mail to MomCRM,
 * and each mailbox added is a routing rule that sends its address's mail to
 * that Worker.
 */
final class CloudflareConnector extends ConnectorType implements ConnectsByForm, ProvidesMail, SetsUpMailboxes
{
    public const string KEY = 'cloudflare';

    public const string COMPANY = 'Cloudflare';

    public const string API_TOKEN = 'api_token';

    private const string ACCOUNT_ID = 'account_id';

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

    public function formIntroduction(): string
    {
        return 'MomCRM puts a small Worker in your Cloudflare account that hands it the mail Email Routing receives, and sends replies through Email Sending. It needs an API token with these permissions: Workers Scripts (Edit), Email Sending (Edit), Email Routing Rules (Edit), Zone (Read) and Zone Settings (Read).';
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
        $deliveryUrl = route('connectors.cloudflare.mail');

        if (! PublicHost::allows((string) parse_url($deliveryUrl, PHP_URL_HOST))) {
            throw CouldNotConnect::because(self::ACCOUNT_ID, (string) __("Cloudflare can't hand mail to MomCRM at :url, because that address can't be reached from the internet.", ['url' => $deliveryUrl]));
        }

        $api = new CloudflareApi($accountId, $token);

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
            $api->deployWorker(
                self::workerName($accountId),
                self::workerScript(),
                ['MOMCRM_URL' => $deliveryUrl],
                ['MOMCRM_SECRET' => self::deliverySecret($accountId)],
            );
        } catch (CouldNotReachConnector $exception) {
            throw CouldNotConnect::because(self::API_TOKEN, $exception->isTemporary
                ? $exception->getMessage()
                : (string) __('MomCRM could not put its Worker in the account. Check that the token has Workers Scripts (Edit).'));
        }

        return new ConnectedAccount(
            account: $accountId,
            name: $zones[0]['account_name'] ?? $this->label(),
            settings: [self::ACCOUNT_ID => $accountId],
            credentials: [self::API_TOKEN => $token],
        );
    }

    public function mailClient(Connection $connection, ?string $sharedAddress = null): MailClient
    {
        return CloudflareMailClient::forConnection($connection, $sharedAddress);
    }

    /**
     * A Cloudflare account has no mailbox of its own: every mailbox is an
     * address on one of its domains.
     */
    public function opensSharedMailboxes(Connection $connection): bool
    {
        return true;
    }

    public function setUpMailbox(Connection $connection, string $address): void
    {
        CloudflareMailClient::forConnection($connection, $address)->setUp();
    }

    public function tearDownMailbox(Connection $connection, string $address): void
    {
        CloudflareMailClient::forConnection($connection, $address)->tearDown();
    }

    /**
     * What the Worker is called in a Cloudflare account. It follows from
     * this installation and the account, so connecting the account again
     * replaces the same Worker, and two installations never share one.
     */
    public static function workerName(string $accountId): string
    {
        return 'momcrm-mail-'.mb_substr(self::keyed("worker|{$accountId}"), 0, 12);
    }

    /**
     * What the Worker in a Cloudflare account proves itself with when it
     * hands mail over. Only Cloudflare and this installation can know it.
     */
    public static function deliverySecret(string $accountId): string
    {
        return self::keyed("delivery|{$accountId}");
    }

    private static function workerScript(): string
    {
        return (string) file_get_contents(__DIR__.'/worker.js');
    }

    private static function keyed(string $value): string
    {
        return hash_hmac('sha256', "cloudflare|{$value}", Config::string('app.key'));
    }
}
