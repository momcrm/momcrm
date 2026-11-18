<?php

declare(strict_types=1);

use App\Mailbox\MailboxSession;
use App\User\UserEloquentModel;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    config(['services.microsoft.client_id' => 'client-id', 'services.microsoft.client_secret' => 'secret']);
});

/**
 * The query string of the Microsoft address a response redirects to.
 *
 * @return array<string, string>
 */
function microsoftRedirectQuery(TestResponse $response): array
{
    $location = (string) $response->headers->get('Location');

    expect($location)->toStartWith('https://login.microsoftonline.com/common/oauth2/v2.0/authorize?');

    parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

    return $query;
}

it('sends an admin to Microsoft asking for their own mailbox', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    $response = actingAs($owner)
        ->withSession([MailboxSession::PENDING_SHARED_ADDRESS => 'left-over@acme.test'])
        ->get(route('mailboxes.connect'))
        ->assertRedirect()
        ->assertSessionMissing(MailboxSession::PENDING_SHARED_ADDRESS);

    $query = microsoftRedirectQuery($response);

    expect($query['redirect_uri'])->toBe(route('mailboxes.callback'))
        ->and($query['client_id'])->toBe('client-id')
        ->and(explode(' ', $query['scope']))->toEqualCanonicalizing(['openid', 'profile', 'User.Read', 'offline_access', 'Mail.ReadWrite', 'Mail.Send']);
});

it('remembers a shared mailbox and asks only for shared access', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    $response = actingAs($owner)
        ->get(route('mailboxes.connect', ['address' => ' Support@Acme.test ']))
        ->assertRedirect()
        ->assertSessionHas(MailboxSession::PENDING_SHARED_ADDRESS, 'support@acme.test');

    expect(explode(' ', microsoftRedirectQuery($response)['scope']))
        ->toEqualCanonicalizing(['openid', 'profile', 'User.Read', 'offline_access', 'Mail.ReadWrite.Shared', 'Mail.Send.Shared']);
});

it('rejects a shared mailbox that is not an email address', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->get(route('mailboxes.connect', ['address' => 'support']))
        ->assertSessionHasErrors(['address' => 'The address field must be a valid email address.']);
});

it('is not found when Microsoft sign-in is not set up', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    config(['services.microsoft.client_id' => null]);

    actingAs($owner)->get(route('mailboxes.connect'))->assertNotFound();
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($member)->get(route('mailboxes.connect'))->assertForbidden();
});
