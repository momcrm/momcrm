<?php

declare(strict_types=1);

use App\Connector\ConnectorEloquentModel;
use App\Connector\Types\Cloudflare\CloudflareConnector;
use App\Mailbox\MailboxEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;

it('removes a mailbox and leaves its connector connected', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $mailbox = MailboxEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'address' => 'support@acme.test']);

    actingAs($owner)
        ->delete(route('mailboxes.destroy', $mailbox->id))
        ->assertRedirectToRoute('mailboxes.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'support@acme.test removed.']);

    expect($mailbox->fresh())->toBeNull()
        ->and(ConnectorEloquentModel::query()->withoutGlobalScopes()->find($mailbox->connector_id)?->status)->toBe('connected');
});

it('forbids members and hides other teams\' mailboxes', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $mailbox = MailboxEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $theirs = MailboxEloquentModel::factory()->create();

    actingAs($member)->delete(route('mailboxes.destroy', $mailbox->id))->assertForbidden();
    actingAs($owner)->delete(route('mailboxes.destroy', $theirs->id))->assertNotFound();

    expect($mailbox->fresh())->not->toBeNull()
        ->and($theirs->fresh())->not->toBeNull();
});

describe('a mailbox that goes through a Cloudflare account', function (): void {
    /**
     * Cloudflare with one routing rule for support@helpdesk.test, handing its
     * mail to a Worker.
     */
    function fakeCloudflareRule(string $worker): void
    {
        Http::fake([
            CLOUDFLARE_API.'/zones?*' => Http::response(cloudflareResult([['id' => 'zone-1', 'name' => 'helpdesk.test']])),
            CLOUDFLARE_API.'/zones/zone-1/email/routing/rules?*' => Http::response(cloudflareResult([[
                'id' => 'rule-1',
                'matchers' => [['type' => 'literal', 'field' => 'to', 'value' => 'support@helpdesk.test']],
                'actions' => [['type' => 'worker', 'value' => [$worker]]],
            ]])),
            CLOUDFLARE_API.'/zones/zone-1/email/routing/rules/rule-1' => Http::response(cloudflareResult(['id' => 'rule-1'])),
        ]);
    }

    it('removes the routing rule that handed its mail to the Worker', function (): void {
        Http::preventStrayRequests();
        $mailbox = cloudflareMailbox();
        $connector = ConnectorEloquentModel::query()->withoutGlobalScopes()->sole();
        $owner = UserEloquentModel::factory()->inTeam($connector->team_id, TeamRoleEnum::Owner)->create();
        fakeCloudflareRule(CloudflareConnector::workerName($connector->account));

        actingAs($owner)
            ->delete(route('mailboxes.destroy', $mailbox->getId()->toString()))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'support@helpdesk.test removed.']);

        expect(MailboxEloquentModel::query()->withoutGlobalScopes()->count())->toBe(0);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && str_ends_with($request->url(), '/zones/zone-1/email/routing/rules/rule-1'));
    });

    it('leaves a routing rule that sends the address\'s mail somewhere else', function (): void {
        Http::preventStrayRequests();
        $mailbox = cloudflareMailbox();
        $owner = UserEloquentModel::factory()->inTeam($mailbox->getTeamId()->toString(), TeamRoleEnum::Owner)->create();
        fakeCloudflareRule('someone-elses-worker');

        actingAs($owner)->delete(route('mailboxes.destroy', $mailbox->getId()->toString()))->assertRedirectToRoute('mailboxes.index');

        expect(MailboxEloquentModel::query()->withoutGlobalScopes()->count())->toBe(0);
        Http::assertNotSent(fn (Request $request): bool => $request->method() === 'DELETE');
    });

    it('removes the mailbox even when Cloudflare cannot be reached', function (): void {
        Http::preventStrayRequests();
        $mailbox = cloudflareMailbox();
        $owner = UserEloquentModel::factory()->inTeam($mailbox->getTeamId()->toString(), TeamRoleEnum::Owner)->create();
        Http::fake([CLOUDFLARE_API.'/*' => Http::response(status: 503)]);

        actingAs($owner)->delete(route('mailboxes.destroy', $mailbox->getId()->toString()))->assertRedirectToRoute('mailboxes.index');

        expect(MailboxEloquentModel::query()->withoutGlobalScopes()->count())->toBe(0);
    });
});
