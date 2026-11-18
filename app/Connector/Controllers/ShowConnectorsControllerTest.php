<?php

declare(strict_types=1);

use App\Connector\ConnectorEloquentModel;
use App\Mailbox\MailboxEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('lists the team\'s connectors without their credentials, with their mailboxes and the types it can add', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $connector = ConnectorEloquentModel::factory()->withSharedMailboxes()->needsReconnect()->create([
        'team_id' => $owner->current_team_id,
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
            ->where('types', [
                ['value' => 'microsoft', 'label' => 'Microsoft 365', 'capabilities' => ['Email'], 'supports_shared_mailboxes' => true, 'uses_sign_in' => true],
                ['value' => 'imap', 'label' => 'IMAP/SMTP', 'capabilities' => ['Email'], 'supports_shared_mailboxes' => false, 'uses_sign_in' => false],
            ])
            ->has('connectors', 1)
            ->where('connectors.0', [
                'id' => $connector->id,
                'type' => 'microsoft',
                'type_label' => 'Microsoft 365',
                'name' => 'Sam Lee',
                'account' => 'sam@acme.test',
                'status' => 'needs_reconnect',
                'capabilities' => ['Email'],
                'shared_mailboxes' => true,
                'last_error' => 'Connect it again.',
                'uses_sign_in' => true,
                'settings' => null,
                'mailboxes' => ['support@acme.test'],
            ]),
        );
});

it('offers every type whose sign-in keys are set', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    config([
        'services.microsoft.client_id' => null,
        'services.google.client_id' => 'google-id',
        'services.google.client_secret' => 'secret',
    ]);

    actingAs($owner)
        ->get(route('connectors.index'))
        ->assertInertia(fn (Assert $page) => $page->where('types', [
            ['value' => 'google', 'label' => 'Google', 'capabilities' => ['Email'], 'supports_shared_mailboxes' => false, 'uses_sign_in' => true],
            ['value' => 'imap', 'label' => 'IMAP/SMTP', 'capabilities' => ['Email'], 'supports_shared_mailboxes' => false, 'uses_sign_in' => false],
        ]));
});

it('always offers IMAP/SMTP, and shows a mail server connector\'s settings without its passwords', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    ConnectorEloquentModel::factory()->imap()->create(['team_id' => $owner->current_team_id, 'account' => 'help@helpdesk.test']);
    config(['services.microsoft.client_id' => null, 'services.google.client_id' => null]);

    actingAs($owner)
        ->get(route('connectors.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('types', [['value' => 'imap', 'label' => 'IMAP/SMTP', 'capabilities' => ['Email'], 'supports_shared_mailboxes' => false, 'uses_sign_in' => false]])
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
