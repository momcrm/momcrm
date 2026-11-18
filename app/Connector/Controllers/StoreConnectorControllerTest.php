<?php

declare(strict_types=1);

use App\Connector\ConnectorEloquentModel;
use App\Connector\ConnectorTypeEnum;
use App\Connector\Exceptions\CouldNotReachConnector;
use App\Mailbox\Contracts\MailServers;
use App\User\UserEloquentModel;
use Tests\Support\FakeMailServers;

use function Pest\Laravel\actingAs;

/**
 * What an admin types in to connect a mailbox on a mail server.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function mailServerInput(array $overrides = []): array
{
    return [
        'from_address' => 'Help@Helpdesk.test',
        'from_name' => 'Help Desk',
        'imap_host' => '203.0.113.10',
        'imap_port' => 993,
        'imap_encryption' => 'ssl',
        'imap_username' => 'help',
        'imap_password' => 'imap-secret',
        'smtp_host' => '203.0.113.20',
        'smtp_port' => 587,
        'smtp_encryption' => 'starttls',
        'smtp_username' => 'help-smtp',
        'smtp_password' => 'smtp-secret',
        ...$overrides,
    ];
}

beforeEach(function (): void {
    app()->instance(MailServers::class, $this->servers = new FakeMailServers);
});

it('connects a mailbox once both of its servers let us in, keeping the passwords encrypted', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->post(route('connectors.store'), mailServerInput())
        ->assertSessionHasNoErrors()
        ->assertRedirectToRoute('connectors.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'help@helpdesk.test connected.']);

    $connector = ConnectorEloquentModel::query()->withoutGlobalScopes()->sole();

    expect($connector)
        ->team_id->toBe($owner->current_team_id)
        ->type->toBe('imap')
        ->name->toBe('Help Desk')
        ->account->toBe('help@helpdesk.test')
        ->status->toBe('connected')
        ->connected_by_id->toBe($owner->id)
        ->credentials->toBe(['imap_password' => 'imap-secret', 'smtp_password' => 'smtp-secret'])
        ->and($connector->settings)->toEqual([
            'from_name' => 'Help Desk',
            'imap_host' => '203.0.113.10',
            'imap_port' => 993,
            'imap_encryption' => 'ssl',
            'imap_username' => 'help',
            'smtp_host' => '203.0.113.20',
            'smtp_port' => 587,
            'smtp_encryption' => 'starttls',
            'smtp_username' => 'help-smtp',
        ])
        ->and($connector->getRawOriginal('credentials'))->not->toContain('imap-secret')->not->toContain('smtp-secret')
        ->and($connector->getRawOriginal('settings'))->not->toContain('secret')
        ->and($this->servers->imapSignIns)->toHaveCount(1)
        ->and($this->servers->imapSignIns[0]->imapPassword)->toBe('imap-secret')
        ->and($this->servers->smtpSignIns)->toHaveCount(1)
        ->and($this->servers->smtpSignIns[0]->smtpUsername)->toBe('help-smtp');
});

it('signs in to SMTP as it does to IMAP when no other username and password are given', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->post(route('connectors.store'), mailServerInput(['from_name' => null, 'smtp_username' => null, 'smtp_password' => null]))
        ->assertSessionHasNoErrors();

    expect(ConnectorEloquentModel::query()->withoutGlobalScopes()->sole())
        ->name->toBe('help@helpdesk.test')
        ->credentials->toBe(['imap_password' => 'imap-secret', 'smtp_password' => 'imap-secret'])
        ->and($this->servers->smtpSignIns[0]->smtpUsername)->toBe('help');
});

it('replaces the servers and passwords of an address the team already connected', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $connector = ConnectorEloquentModel::factory()->imap()->needsReconnect()->create([
        'team_id' => $owner->current_team_id,
        'account' => 'help@helpdesk.test',
        'last_error' => 'Connect it again.',
    ]);

    actingAs($owner)
        ->post(route('connectors.store'), mailServerInput(['imap_password' => 'new-secret', 'imap_host' => '203.0.113.99']))
        ->assertSessionHasNoErrors();

    expect($connector->refresh())
        ->status->toBe('connected')
        ->last_error->toBeNull()
        ->and($connector->credentials['imap_password'])->toBe('new-secret')
        ->and($connector->settings['imap_host'] ?? null)->toBe('203.0.113.99')
        ->and(ConnectorEloquentModel::query()->withoutGlobalScopes()->count())->toBe(1);
});

it('saves nothing and says which field to look at', function (Closure $setUp, array $errors): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->post(route('connectors.store'), $setUp($this->servers))
        ->assertSessionHasErrors($errors);

    expect(ConnectorEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
})->with([
    'nothing filled in' => [
        fn () => [],
        ['from_address', 'imap_host', 'imap_port', 'imap_encryption', 'imap_username', 'imap_password', 'smtp_host', 'smtp_port', 'smtp_encryption'],
    ],
    'a web address for a server' => [
        fn () => mailServerInput(['imap_host' => 'https://imap.acme.test/inbox']),
        ['imap_host' => 'Enter the server\'s name, such as imap.yourcompany.com, without "https://" or a path.'],
    ],
    'a port that cannot be one' => [
        fn () => mailServerInput(['smtp_port' => 70000]),
        ['smtp_port' => 'The smtp port field must be between 1 and 65535.'],
    ],
    'an encryption we do not know' => [
        fn () => mailServerInput(['imap_encryption' => 'rot13']),
        ['imap_encryption' => 'The selected imap encryption is invalid.'],
    ],
    'an IMAP server on this machine' => [
        fn () => mailServerInput(['imap_host' => '127.0.0.1']),
        ['imap_host' => "MomCRM can't connect to 127.0.0.1. Check the name, and that the server can be reached from the internet."],
    ],
    'an SMTP server on a private network' => [
        fn () => mailServerInput(['smtp_host' => '10.0.0.5']),
        ['smtp_host' => "MomCRM can't connect to 10.0.0.5. Check the name, and that the server can be reached from the internet."],
    ],
    'an IMAP server that refuses the password' => [
        function (FakeMailServers $servers): array {
            $servers->imapFailure = CouldNotReachConnector::needsReconnect(ConnectorTypeEnum::Imap);

            return mailServerInput();
        },
        ['imap_password' => 'The IMAP server did not accept that username and password.'],
    ],
    'an IMAP server that cannot be reached' => [
        function (FakeMailServers $servers): array {
            $servers->imapFailure = CouldNotReachConnector::unavailable(ConnectorTypeEnum::Imap);

            return mailServerInput();
        },
        ['imap_host' => "We couldn't reach the IMAP server at 203.0.113.10 on port 993. Check the server, port and encryption."],
    ],
    'an SMTP server that refuses the password' => [
        function (FakeMailServers $servers): array {
            $servers->smtpFailure = CouldNotReachConnector::needsReconnect(ConnectorTypeEnum::Imap);

            return mailServerInput();
        },
        ['smtp_password' => 'The SMTP server did not accept that username and password.'],
    ],
    'an SMTP server that cannot be reached' => [
        function (FakeMailServers $servers): array {
            $servers->smtpFailure = CouldNotReachConnector::unavailable(ConnectorTypeEnum::Imap);

            return mailServerInput();
        },
        ['smtp_host' => "We couldn't reach the SMTP server at 203.0.113.20 on port 587. Check the server, port and encryption."],
    ],
]);

it('signs in to no server it may not connect to', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)->post(route('connectors.store'), mailServerInput(['smtp_host' => '169.254.169.254']))->assertSessionHasErrors('smtp_host');

    expect($this->servers->imapSignIns)->toBe([])
        ->and($this->servers->smtpSignIns)->toBe([]);
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($member)->post(route('connectors.store'), mailServerInput())->assertForbidden();

    expect(ConnectorEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
});
