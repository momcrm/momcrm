<?php

declare(strict_types=1);

use App\Attachment\AttachmentEloquentModel;
use App\Connector\ConnectorEloquentModel;
use App\Connector\Types\Cloudflare\DeliveredMail;
use App\Mailbox\Jobs\TakeInDeliveredMailJob;
use App\Mailbox\MailboxEloquentModel;
use App\MailboxMessage\MailboxMessageEloquentModel;
use App\Team\TeamEloquentModel;
use App\Ticket\TicketEloquentModel;
use Illuminate\Support\Facades\Storage;

it('opens a ticket, with its file, from an email handed over for the mailbox', function (): void {
    Storage::fake();
    $mailbox = cloudflareMailbox();
    $id = DeliveredMail::forAddress('support@helpdesk.test')->keep(emailSource(withFile: true));

    TakeInDeliveredMailJob::dispatch($mailbox->getId()->toString(), $id);

    $ticket = TicketEloquentModel::query()->withoutGlobalScopes()->sole();

    expect($ticket)
        ->title->toBe('Printer offline')
        ->requester_email->toBe('sam@acme.test')
        ->requester_name->toBe('Sam Lee')
        ->mailbox_id->toBe($mailbox->getId()->toString())
        ->and(MailboxMessageEloquentModel::query()->withoutGlobalScopes()->sole())
        ->external_id->toBe($id)
        ->internet_message_id->toBe('message-1@acme.test')
        ->ticket_id->toBe($ticket->id)
        ->and(AttachmentEloquentModel::query()->withoutGlobalScopes()->sole()->name)->toBe('log.txt');
});

it('takes an email in once, however often it is queued', function (): void {
    Storage::fake();
    $mailbox = cloudflareMailbox();
    $id = DeliveredMail::forAddress('support@helpdesk.test')->keep(emailSource());

    TakeInDeliveredMailJob::dispatch($mailbox->getId()->toString(), $id);
    TakeInDeliveredMailJob::dispatch($mailbox->getId()->toString(), $id);

    expect(TicketEloquentModel::query()->withoutGlobalScopes()->count())->toBe(1);
});

it('leaves the email where it is when the mailbox is not being read', function (Closure $stopReading): void {
    Storage::fake();
    $mailbox = cloudflareMailbox();
    $id = DeliveredMail::forAddress('support@helpdesk.test')->keep(emailSource());
    $stopReading($mailbox->getTeamId()->toString(), $mailbox->getConnectorId()->toString(), $mailbox->getId()->toString());

    TakeInDeliveredMailJob::dispatch($mailbox->getId()->toString(), $id);

    expect(TicketEloquentModel::query()->withoutGlobalScopes()->count())->toBe(0)
        ->and(DeliveredMail::forAddress('support@helpdesk.test')->source($id))->not->toBeNull();
})->with([
    'its team is suspended' => [fn (string $teamId) => TeamEloquentModel::query()->whereKey($teamId)->update(['suspended_at' => now()])],
    'its connector needs reconnecting' => [fn (string $teamId, string $connectorId) => ConnectorEloquentModel::query()->withoutGlobalScopes()->whereKey($connectorId)->update(['status' => 'needs_reconnect'])],
    'it was removed' => [fn (string $teamId, string $connectorId, string $mailboxId) => MailboxEloquentModel::query()->withoutGlobalScopes()->whereKey($mailboxId)->delete()],
]);

it('does nothing for an email that has been cleared out', function (): void {
    Storage::fake();
    $mailbox = cloudflareMailbox();

    TakeInDeliveredMailJob::dispatch($mailbox->getId()->toString(), '1795000000000000-'.str_repeat('a', 32));

    expect(TicketEloquentModel::query()->withoutGlobalScopes()->count())->toBe(0);
});
