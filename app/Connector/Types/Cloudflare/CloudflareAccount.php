<?php

declare(strict_types=1);

namespace App\Connector\Types\Cloudflare;

use App\Mailbox\Contracts\SetsUpMailboxes;
use Illuminate\Support\Facades\Config;
use Mom\Connectors\Connection;
use Mom\Connectors\ConnectorType;
use Mom\Connectors\CouldNotReachConnector;
use Mom\Connectors\Mail\MailClient;
use Mom\Connectors\Mail\ProvidesMail;
use Mom\Connectors\PublicHost;

/**
 * A Cloudflare account, however it was connected: by signing in or with an
 * API token. Its Email Routing hands MomCRM the mail that arrives for
 * addresses on the account's domains, and its Email Sending sends the replies.
 *
 * Cloudflare has no inbox to read: it passes each email on as it arrives. So
 * MomCRM puts a small Worker in the account that passes mail to it, and each
 * mailbox added is a routing rule that sends its address's mail to that
 * Worker.
 */
abstract class CloudflareAccount extends ConnectorType implements ProvidesMail, SetsUpMailboxes
{
    public const string COMPANY = 'Cloudflare';

    public function company(): string
    {
        return self::COMPANY;
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
     * Puts the Worker in a Cloudflare account, or replaces the one already
     * there, with where to hand mail over and the secret that proves it is
     * this Worker. That is how a moved MomCRM or a changed Worker reaches
     * Cloudflare.
     *
     * @throws CouldNotReachConnector
     */
    public static function deployWorker(CloudflareApi $api, string $accountId): void
    {
        if (! self::canBeHandedMail()) {
            throw CouldNotReachConnector::refused(self::whyNoMailCanBeHandedOver());
        }

        $api->deployWorker(
            self::workerName($accountId),
            (string) file_get_contents(__DIR__.'/worker.js'),
            ['MOMCRM_URL' => route('connectors.cloudflare.mail')],
            ['MOMCRM_SECRET' => self::deliverySecret($accountId)],
        );
    }

    /**
     * Whether Cloudflare could reach this installation to hand mail over.
     * One that is only on this machine or a private network could never
     * receive any.
     */
    public static function canBeHandedMail(): bool
    {
        return PublicHost::allows((string) parse_url(route('connectors.cloudflare.mail'), PHP_URL_HOST));
    }

    public static function whyNoMailCanBeHandedOver(): string
    {
        return (string) __("Cloudflare can't hand mail to MomCRM at :url, because that address can't be reached from the internet.", ['url' => route('connectors.cloudflare.mail')]);
    }

    /**
     * What the Worker is called in a Cloudflare account. It follows from
     * this installation and the account, so deploying it again replaces the
     * same Worker, and two installations never share one.
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

    private static function keyed(string $value): string
    {
        return hash_hmac('sha256', "cloudflare|{$value}", Config::string('app.key'));
    }
}
