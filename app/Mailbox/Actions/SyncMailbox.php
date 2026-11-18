<?php

declare(strict_types=1);

namespace App\Mailbox\Actions;

use App\Connector\Actions\FindConnector;
use App\Connector\Exceptions\CouldNotReachConnector;
use App\Mailbox\Mailbox;
use App\Mailbox\MailboxEloquentModel;
use App\Mailbox\MicrosoftGraph;
use App\Mailbox\Properties\LastError;
use App\Mailbox\Properties\LastSyncedAt;
use App\Mailbox\Properties\SyncedThrough;
use App\MailboxMessage\Actions\FindMailboxMessage;
use App\MailboxMessage\Actions\ProcessInboundMessage;
use App\MailboxMessage\Actions\SaveMessageAttachments;
use App\MailboxMessage\MailboxMessage;
use App\Team\Scopes\CurrentTeamScope;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Reads the mail that reached a mailbox's inbox since it was last read, and
 * takes in each message that is new.
 *
 * Trouble reaching Microsoft stops the read where it is: the mailbox keeps its
 * place and the error, and the next read starts over from there. A message we
 * cannot make sense of is our fault, not Microsoft's, so it is reported and
 * skipped and the read carries on.
 */
final readonly class SyncMailbox
{
    /**
     * Look this far back past the last read, for mail that arrived a little
     * before it showed up in the inbox.
     */
    private const int OVERLAP_IN_MINUTES = 10;

    /**
     * The most messages one read takes in, which keeps it well inside the
     * time a queued job is given. The next read carries on from there.
     */
    public const int MAX_MESSAGES = 50;

    public function __construct(
        private Mailbox $mailbox,
    ) {}

    /**
     * The number of messages taken in.
     */
    public function __invoke(): int
    {
        if ($this->mailbox->isNull() || ! FindConnector::byId($this->mailbox->getConnectorId()->toNullableString())->isConnected()) {
            return 0;
        }

        $startedAt = CarbonImmutable::now();
        $taken = 0;

        try {
            $graph = MicrosoftGraph::forMailbox($this->mailbox);
            $listed = $graph->inboxMessagesSince($this->since(), self::MAX_MESSAGES);
            $known = FindMailboxMessage::knownExternalIds(
                $this->mailbox->getId()->toString(),
                $this->mailbox->getTeamId()->toString(),
                array_column($listed, 'id'),
            );

            foreach ($listed as $item) {
                if (in_array($item['id'], $known, true)) {
                    continue;
                }

                $message = $graph->message($item['id']);

                if ($message !== null && $this->takeIn($graph, $message)) {
                    $taken++;
                }
            }
        } catch (CouldNotReachConnector $exception) {
            $this->save([LastError::getDatabaseTableColumnName() => $exception->getMessage()]);

            return $taken;
        }

        // A full read may have left mail behind, so the next one starts at its last message.
        $last = $listed === [] ? null : $listed[array_key_last($listed)]['received_at'];

        $this->save([
            SyncedThrough::getDatabaseTableColumnName() => count($listed) >= self::MAX_MESSAGES && $last !== null ? $last : $startedAt,
            LastSyncedAt::getDatabaseTableColumnName() => $startedAt,
            LastError::getDatabaseTableColumnName() => null,
        ]);

        return $taken;
    }

    public static function fromMailbox(Mailbox $mailbox): int
    {
        return new self($mailbox)->handle();
    }

    public function handle(): int
    {
        return $this->__invoke();
    }

    public function execute(): int
    {
        return $this->__invoke();
    }

    /**
     * Where to read from: a little before the last read, but never before the
     * mailbox was connected, so mail already in it then is left alone.
     */
    private function since(): CarbonImmutable
    {
        $connectedAt = $this->mailbox->getCreatedAt()->toNullableCarbon() ?? CarbonImmutable::now();
        $since = ($this->mailbox->getSyncedThrough()->toNullableCarbon() ?? $connectedAt)->subMinutes(self::OVERLAP_IN_MINUTES);

        return $since->max($connectedAt);
    }

    /**
     * Whether the message was new and has been kept. Its attachments are
     * saved on its ticket afterwards, and are the one thing not tried again:
     * if they cannot be fetched the ticket still stands, and the failure is
     * reported.
     *
     * @param  array<string, mixed>  $message
     */
    private function takeIn(MicrosoftGraph $graph, array $message): bool
    {
        if (($message['isDraft'] ?? false) === true) {
            return false;
        }

        try {
            $kept = ProcessInboundMessage::fromMessage($this->mailbox, MailboxMessage::fromMicrosoftGraph($message));
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }

        if ($kept->isNotNull() && $kept->getTicketId()->isNotNull() && ($message['hasAttachments'] ?? false) === true) {
            try {
                SaveMessageAttachments::fromMessage($graph, $kept);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $kept->isNotNull();
    }

    /**
     * Written straight to the table: these change on every read, and are not
     * changes anyone made, so they stay out of the audit log.
     *
     * @param  array<string, mixed>  $values
     */
    private function save(array $values): void
    {
        MailboxEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->whereKey($this->mailbox->getId()->toString())
            ->update($values);
    }
}
