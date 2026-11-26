<?php

declare(strict_types=1);

namespace App\Connector\Types\Cloudflare;

use App\Connector\Actions\MarkConnectorAsNeedingReconnect;
use App\Connector\Connector;
use App\MailboxMessage\MailHeaders;
use App\MailboxMessage\Properties\InternetMessageId;
use App\MailboxMessage\RawMail;
use Carbon\CarbonImmutable;
use Closure;
use Mom\Connectors\Connection;
use Mom\Connectors\CouldNotReachConnector;
use Mom\Connectors\Mail\InboundMail;
use Mom\Connectors\Mail\MailClient;
use Mom\Connectors\Mail\OutgoingMail;
use Mom\Connectors\Mail\SentMail;

/**
 * An address on one of a Cloudflare account's domains. Email Routing hands
 * its mail to MomCRM as it arrives, so the inbox read here is the mail that
 * has been handed over and kept, not anything at Cloudflare. Replies go out
 * through Email Sending.
 */
final class CloudflareMailClient implements MailClient
{
    /**
     * How long an email is left to the job that takes it in as it arrives
     * before a read of the inbox lists it too. A read is for what that job
     * missed, and the two should not take in the same email at once.
     */
    public const int SECONDS_LEFT_TO_ARRIVE = 60;

    /**
     * Emails already read by this client, by their id.
     *
     * @var array<string, ?RawMail>
     */
    private array $read = [];

    private function __construct(
        private readonly Connection $connection,
        private readonly ?string $address,
    ) {}

    public static function forConnection(Connection $connection, ?string $address): self
    {
        return new self($connection, $address === null ? null : mb_strtolower($address));
    }

    /**
     * The mailbox can be read when Cloudflare is handing its mail to this
     * installation's Worker.
     */
    public function checkInbox(): void
    {
        $address = $this->address();

        $rule = $this->asking(function (CloudflareApi $api) use ($address): ?array {
            $zone = $api->zoneFor(self::domain($address));

            return $zone === null ? null : $api->ruleFor($zone['id'], $address);
        });

        if ($rule === null || $rule['worker'] !== $this->worker()) {
            throw CouldNotReachConnector::refused((string) __("Cloudflare is not handing :address's mail to MomCRM. Remove the mailbox and add it again.", ['address' => $address]));
        }
    }

    /**
     * Adds the routing rule that hands the address's mail to the Worker. An
     * address that already goes somewhere else is left as it is: taking it
     * over would cut off whoever gets that mail now.
     *
     * @throws CouldNotReachConnector
     */
    public function setUp(): void
    {
        $address = $this->address();
        $domain = self::domain($address);

        $this->asking(function (CloudflareApi $api) use ($address, $domain): void {
            $zone = $api->zoneFor($domain);

            if ($zone === null) {
                throw CouldNotReachConnector::refused((string) __(':domain is not one of the domains the API token covers in this Cloudflare account.', ['domain' => $domain]));
            }

            if ($api->routingIsOn($zone['id']) === false) {
                throw CouldNotReachConnector::refused((string) __('Email Routing is not turned on for :domain. Turn it on in Cloudflare, under Email Service, then add the mailbox again.', ['domain' => $zone['name']]));
            }

            $rule = $api->ruleFor($zone['id'], $address);

            if ($rule === null) {
                $api->createRule($zone['id'], $address, $this->worker());
            } elseif ($rule['worker'] !== $this->worker()) {
                throw CouldNotReachConnector::refused((string) __("Cloudflare already sends :address's mail somewhere else. Remove that routing rule in Cloudflare, then add the mailbox again.", ['address' => $address]));
            }
        });
    }

    /**
     * Removes the routing rule, when it is the one that hands mail to the
     * Worker. Mail to the address then goes wherever the domain's other
     * rules send it.
     *
     * @throws CouldNotReachConnector
     */
    public function tearDown(): void
    {
        $address = $this->address();

        $this->asking(function (CloudflareApi $api) use ($address): void {
            $zone = $api->zoneFor(self::domain($address));
            $rule = $zone === null ? null : $api->ruleFor($zone['id'], $address);

            if ($zone !== null && $rule !== null && $rule['worker'] === $this->worker()) {
                $api->deleteRule($zone['id'], $rule['id']);
            }
        });
    }

    public function inboxMessagesSince(CarbonImmutable $since, int $limit): array
    {
        return $this->delivered()->between($since, CarbonImmutable::now()->subSeconds(self::SECONDS_LEFT_TO_ARRIVE), $limit);
    }

    public function message(string $id): ?InboundMail
    {
        $mail = $this->raw($id);

        return $mail === null
            ? null
            : MailHeaders::toInboundMail($mail->headers, $id, $mail->bodyText(), DeliveredMail::receivedAt($id), $mail->attachments !== []);
    }

    /**
     * A file is known by its place among the email's files.
     */
    public function attachments(string $messageId): array
    {
        $attachments = [];

        foreach ($this->raw($messageId)->attachments ?? [] as $index => $attachment) {
            $attachments[] = [
                'id' => (string) $index,
                'name' => $attachment['name'],
                'content_type' => $attachment['content_type'],
                'size' => $attachment['size'],
                'is_file' => true,
                'is_inline' => $attachment['is_inline'],
            ];
        }

        return $attachments;
    }

    public function attachmentContents(string $messageId, string $attachmentId): string
    {
        $attachment = ctype_digit($attachmentId) ? ($this->raw($messageId)->attachments[(int) $attachmentId] ?? null) : null;

        if ($attachment === null) {
            throw CouldNotReachConnector::refused((string) __('That attachment is no longer on the message.'));
        }

        return $attachment['content'];
    }

    /**
     * Cloudflare gives every email it sends a Message-ID of its own and
     * says what it is, which is what the customer's answer will refer to.
     */
    public function send(OutgoingMail $mail): SentMail
    {
        $address = $this->address();
        $subject = $mail->subjectLine();
        $answered = $mail->answered;
        $threadIds = $answered?->messageId === null ? [] : [...$answered->references, $answered->messageId];

        $sent = $this->asking(fn (CloudflareApi $api): array => $api->send([
            'from' => $address,
            'to' => $mail->to,
            'subject' => $subject,
            'text' => $mail->bodyText,
            'html' => $mail->bodyHtml(),
            ...($threadIds === [] ? [] : ['headers' => [
                'In-Reply-To' => '<'.$threadIds[array_key_last($threadIds)].'>',
                'References' => implode(' ', array_map(fn (string $id): string => "<{$id}>", $threadIds)),
            ]]),
        ]));

        $reached = [...self::addresses($sent['delivered'] ?? null), ...self::addresses($sent['queued'] ?? null)];
        $bounced = self::addresses($sent['permanent_bounces'] ?? null);

        if ($reached === [] && $bounced !== []) {
            throw CouldNotReachConnector::refused((string) __('Cloudflare could not deliver to :addresses.', ['addresses' => implode(', ', $bounced)]));
        }

        return new SentMail(null, InternetMessageId::normalize($sent['message_id'] ?? null), $subject);
    }

    /**
     * Asks Cloudflare for something. When it no longer accepts the token,
     * nothing will work until the account is connected again, so the
     * connector says so.
     *
     * @template TAnswer
     *
     * @param  Closure(CloudflareApi): TAnswer  $ask
     * @return TAnswer
     *
     * @throws CouldNotReachConnector
     */
    private function asking(Closure $ask): mixed
    {
        try {
            return $ask(CloudflareApi::forConnection($this->connection));
        } catch (CouldNotReachConnector $exception) {
            throw $exception->needsReconnect && $this->connection instanceof Connector
                ? MarkConnectorAsNeedingReconnect::fromConnector($this->connection)
                : $exception;
        }
    }

    /**
     * The email with this id; null when it has been cleared out or cannot
     * be read.
     */
    private function raw(string $id): ?RawMail
    {
        if (! array_key_exists($id, $this->read)) {
            $source = $this->address === null ? null : $this->delivered()->source($id);

            $this->read[$id] = $source === null ? null : RawMail::read($source);
        }

        return $this->read[$id];
    }

    private function delivered(): DeliveredMail
    {
        return DeliveredMail::forAddress($this->address());
    }

    /**
     * @throws CouldNotReachConnector
     */
    private function address(): string
    {
        return $this->address ?? throw CouldNotReachConnector::refused((string) __('A Cloudflare account has no mailbox of its own. Add one by its address.'));
    }

    private function worker(): string
    {
        return CloudflareConnector::workerName($this->connection->account());
    }

    private static function domain(string $address): string
    {
        return mb_substr((string) mb_strrchr($address, '@'), 1);
    }

    /**
     * @return list<string>
     */
    private static function addresses(mixed $value): array
    {
        return array_values(array_filter(is_array($value) ? $value : [], is_string(...)));
    }
}
