<?php

declare(strict_types=1);

use App\Connector\ConnectorEloquentModel;
use App\Mailbox\MailboxEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('lists the team\'s connectors without their credentials, with their mailboxes and the types it can add', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['name' => 'Jane Owner']);
    $connector = ConnectorEloquentModel::factory()->withSharedMailboxes()->needsReconnect()->create([
        'team_id' => $owner->current_team_id,
        'connected_by_id' => $owner->id,
        'name' => 'Sam Lee',
        'account' => 'sam@acme.test',
        'last_error' => 'Connect it again.',
    ]);
    MailboxEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'connector_id' => $connector->id, 'address' => 'support@acme.test']);
    ConnectorEloquentModel::factory()->create(['account' => 'other@elsewhere.test']);
    config(['services.microsoft.client_id' => 'id', 'services.microsoft.client_secret' => 'secret', 'services.google.client_id' => null]);

    actingAs($owner)
        ->get(route('connectors.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/connectors')
            ->has('types', 3)
            ->where('types.0.value', 'microsoft')
            ->where('types.0.label', 'Microsoft 365')
            ->where('types.0.description', 'Outlook and Exchange Online mailboxes, including shared mailboxes.')
            ->where('types.0.capabilities', ['Email'])
            ->where('types.0.available', true)
            ->where('types.0.connection', 'sign_in')
            ->where('types.0.options', [[
                'key' => 'shared_mailboxes',
                'label' => 'This account opens shared mailboxes',
                'hint' => 'Such as support@yourcompany.com. Work and school accounts only.',
            ]])
            ->where('types.0.fields', [])
            ->where('types.0.logo', fn (string $logo): bool => str_starts_with($logo, 'data:image/svg+xml;base64,'))
            ->where('types.1.value', 'google')
            ->where('types.1.available', false)
            ->where('types.1.options', [])
            ->has('connectors', 1)
            ->where('connectors.0', [
                'id' => $connector->id,
                'type' => 'microsoft',
                'type_label' => 'Microsoft 365',
                'name' => 'Sam Lee',
                'account' => 'sam@acme.test',
                'status' => 'needs_reconnect',
                'capabilities' => ['Email'],
                'options' => ['Shared mailboxes'],
                'last_error' => 'Connect it again.',
                'uses_sign_in' => true,
                'settings' => null,
                'connected_at' => $connector->created_at?->toIso8601String(),
                'connected_by' => 'Jane Owner',
                'mailboxes' => ['support@acme.test'],
            ]),
        );
});

it('lists every type, saying which ones this installation is set up for', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    config([
        'services.microsoft.client_id' => null,
        'services.google.client_id' => 'google-id',
        'services.google.client_secret' => 'secret',
    ]);

    actingAs($owner)
        ->get(route('connectors.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('types.0.value', 'microsoft')
            ->where('types.0.available', false)
            ->where('types.1.value', 'google')
            ->where('types.1.available', true)
            ->where('types.2.value', 'imap')
            ->where('types.2.available', true),
        );
});

it('can always connect IMAP/SMTP, and shows a mail server connector\'s settings without its passwords', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    ConnectorEloquentModel::factory()->imap()->create(['team_id' => $owner->current_team_id, 'account' => 'help@helpdesk.test']);
    config(['services.microsoft.client_id' => null, 'services.google.client_id' => null]);

    actingAs($owner)
        ->get(route('connectors.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('types.2.value', 'imap')
            ->where('types.2.connection', 'form')
            ->where('types.2.account_field', 'from_address')
            ->where('types.2.form_introduction', fn (string $introduction): bool => str_contains($introduction, 'IMAP'))
            ->has('types.2.fields', 12)
            ->where('types.2.fields.2', [
                'name' => 'imap_host',
                'label' => 'Server',
                'kind' => 'text',
                'required' => true,
                'placeholder' => 'imap.yourcompany.com',
                'hint' => null,
                'default' => null,
                'options' => [],
                'group' => 'Incoming mail (IMAP)',
                'span' => 3,
            ])
            ->where('types.2.fields.4.options', [
                ['value' => 'ssl', 'label' => 'SSL/TLS'],
                ['value' => 'starttls', 'label' => 'STARTTLS'],
                ['value' => 'none', 'label' => 'None'],
            ])
            ->where('connectors.0.connected_by', null)
            ->where('connectors.0.uses_sign_in', false)
            ->where('connectors.0.settings', [
                'from_name' => 'Help Desk',
                'imap_host' => '203.0.113.10',
                'imap_port' => 993,
                'imap_encryption' => 'ssl',
                'imap_username' => 'help',
                'smtp_host' => '203.0.113.20',
                'smtp_port' => 587,
                'smtp_encryption' => 'starttls',
                'smtp_username' => 'help',
            ]),
        );
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($member)->get(route('connectors.index'))->assertForbidden();
});

it('sends guests to sign in', function (): void {
    get(route('connectors.index'))->assertRedirectToRoute('login');
});

it('still lists a connector whose type is no longer installed, as one that can do nothing', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    ConnectorEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'type' => 'carrier_pigeon']);

    actingAs($owner)
        ->get(route('connectors.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('connectors.0.type', 'carrier_pigeon')
            ->where('connectors.0.type_label', 'Carrier Pigeon')
            ->where('connectors.0.capabilities', [])
            ->where('connectors.0.uses_sign_in', false)
            ->where('connectors.0.settings', null),
        );
});
