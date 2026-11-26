<?php

declare(strict_types=1);

use App\Connector\ConnectorEloquentModel;
use App\Connector\Types\Cloudflare\CloudflareConnector;
use App\Connector\Types\Cloudflare\DeliveredMail;
use App\Mailbox\Jobs\TakeInDeliveredMailJob;
use App\Mailbox\Mailbox;
use App\Mailbox\MailboxEloquentModel;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\call;
use function Pest\Laravel\freezeSecond;

/**
 * The Worker handing an email over, the way worker.js does.
 */
function handOver(string $address, ?string $secret, string $source): TestResponse
{
    return call('POST', route('connectors.cloudflare.mail'), server: [
        'CONTENT_TYPE' => 'message/rfc822',
        'HTTP_X_MAIL_FROM' => rawurlencode('bounces+sam@acme.test'),
        'HTTP_X_MAIL_TO' => rawurlencode($address),
        ...($secret === null ? [] : ['HTTP_AUTHORIZATION' => "Bearer {$secret}"]),
    ], content: $source);
}

/**
 * The secret the Worker in a mailbox's Cloudflare account was given.
 */
function workerSecret(Mailbox $mailbox): string
{
    return CloudflareConnector::deliverySecret(
        ConnectorEloquentModel::query()->withoutGlobalScopes()->findOrFail($mailbox->getConnectorId()->toString())->account,
    );
}

it('keeps an email the Worker hands over and queues taking it in, with nobody signed in', function (): void {
    Storage::fake();
    Queue::fake([TakeInDeliveredMailJob::class]);
    freezeSecond();
    $mailbox = cloudflareMailbox();

    handOver('Support@Helpdesk.test', workerSecret($mailbox), emailSource())->assertNoContent();

    $kept = DeliveredMail::forAddress('support@helpdesk.test')->between(now()->toImmutable()->subMinute(), now()->toImmutable(), 10);

    expect($kept)->toHaveCount(1)
        ->and(DeliveredMail::forAddress('support@helpdesk.test')->source($kept[0]['id']))->toBe(emailSource());

    Queue::assertPushed(
        TakeInDeliveredMailJob::class,
        fn (TakeInDeliveredMailJob $job): bool => $job->mailboxId === $mailbox->getId()->toString() && $job->externalId === $kept[0]['id'],
    );
});

it('returns 404 and keeps nothing for anyone who is not the Worker of the mailbox\'s own account', function (string $address, Closure $secret): void {
    Storage::fake();
    Queue::fake([TakeInDeliveredMailJob::class]);
    $mailbox = cloudflareMailbox();
    $other = cloudflareMailbox(['address' => 'help@elsewhere.test']);
    MailboxEloquentModel::factory()->create(['address' => 'sam@microsoft.test']);

    handOver($address, $secret($mailbox, $other), emailSource())->assertNotFound();

    expect(Storage::allFiles())->toBe([]);
    Queue::assertNotPushed(TakeInDeliveredMailJob::class);
})->with([
    'no secret' => ['support@helpdesk.test', fn (): ?string => null],
    'a wrong secret' => ['support@helpdesk.test', fn (): string => str_repeat('a', 64)],
    'the secret of another Cloudflare account' => ['support@helpdesk.test', fn (Mailbox $mailbox, Mailbox $other): string => workerSecret($other)],
    'an address that is not a mailbox' => ['nobody@helpdesk.test', fn (Mailbox $mailbox): string => workerSecret($mailbox)],
    'a mailbox that goes through another kind of account' => ['sam@microsoft.test', fn (Mailbox $mailbox): string => workerSecret($mailbox)],
    'no address' => ['', fn (Mailbox $mailbox): string => workerSecret($mailbox)],
]);

it('returns 422 for a hand-over with no email in it', function (): void {
    Storage::fake();
    Queue::fake([TakeInDeliveredMailJob::class]);
    $mailbox = cloudflareMailbox();

    handOver('support@helpdesk.test', workerSecret($mailbox), '')->assertUnprocessable();

    Queue::assertNotPushed(TakeInDeliveredMailJob::class);
});

it('keeps an email once when the Worker hands it over again', function (): void {
    Storage::fake();
    Queue::fake([TakeInDeliveredMailJob::class]);
    $mailbox = cloudflareMailbox();

    handOver('support@helpdesk.test', workerSecret($mailbox), emailSource())->assertNoContent();
    handOver('support@helpdesk.test', workerSecret($mailbox), emailSource())->assertNoContent();

    expect(Storage::allFiles())->toHaveCount(1);
    Queue::assertPushed(TakeInDeliveredMailJob::class, 2);
});
