<?php

declare(strict_types=1);

use App\Connector\ConnectorEloquentModel;
use App\Group\GroupEloquentModel;
use App\Mailbox\MailboxEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('lists the team\'s mailboxes with how their connector is doing, and its groups', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $group = GroupEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'name' => 'Helpdesk']);
    $connector = ConnectorEloquentModel::factory()->needsReconnect()->create([
        'team_id' => $owner->current_team_id,
        'name' => 'Sam Lee',
        'last_error' => 'Connect it again.',
    ]);
    $mailbox = MailboxEloquentModel::factory()->shared()->create([
        'team_id' => $owner->current_team_id,
        'connector_id' => $connector->id,
        'address' => 'support@acme.test',
        'group_id' => $group->id,
    ]);
    MailboxEloquentModel::factory()->create(['address' => 'other@elsewhere.test']);

    actingAs($owner)
        ->get(route('mailboxes.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/email')
            ->where('groups', [['id' => $group->id, 'name' => 'Helpdesk']])
            ->has('mailboxes', 1)
            ->where('mailboxes.0', [
                'id' => $mailbox->id,
                'address' => 'support@acme.test',
                'display_name' => null,
                'is_shared' => true,
                'status' => 'needs_reconnect',
                'connector_id' => $connector->id,
                'connector_name' => 'Sam Lee',
                'connector_type' => 'Microsoft 365',
                'group_id' => $group->id,
                'auto_create_tickets' => true,
                'last_synced_at' => null,
                'last_error' => 'Connect it again.',
            ]),
        );
});

it('offers the team\'s working mail connectors to add a mailbox through', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $connector = ConnectorEloquentModel::factory()->withSharedMailboxes()->create([
        'team_id' => $owner->current_team_id,
        'name' => 'Sam Lee',
        'account' => 'sam@acme.test',
    ]);
    ConnectorEloquentModel::factory()->needsReconnect()->create(['team_id' => $owner->current_team_id]);
    ConnectorEloquentModel::factory()->create();

    actingAs($owner)
        ->get(route('mailboxes.index'))
        ->assertInertia(fn (Assert $page) => $page->where('connectors', [[
            'id' => $connector->id,
            'name' => 'Sam Lee',
            'account' => 'sam@acme.test',
            'type_label' => 'Microsoft 365',
            'shared_mailboxes' => true,
            'has_own_mailbox' => true,
        ]]));
});

it('says an account that is not an address has no mailbox of its own, and does not call its mailboxes shared', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $connector = ConnectorEloquentModel::factory()->cloudflare()->create(['team_id' => $owner->current_team_id, 'name' => 'Acme']);
    MailboxEloquentModel::factory()->create([
        'team_id' => $owner->current_team_id,
        'connector_id' => $connector->id,
        'address' => 'support@acme.test',
        'is_shared' => true,
    ]);

    actingAs($owner)
        ->get(route('mailboxes.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('connectors.0.type_label', 'Cloudflare')
            ->where('connectors.0.shared_mailboxes', true)
            ->where('connectors.0.has_own_mailbox', false)
            ->where('mailboxes.0.address', 'support@acme.test')
            ->where('mailboxes.0.is_shared', false),
        );
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($member)->get(route('mailboxes.index'))->assertForbidden();
});

it('sends guests to sign in', function (): void {
    get(route('mailboxes.index'))->assertRedirectToRoute('login');
});
