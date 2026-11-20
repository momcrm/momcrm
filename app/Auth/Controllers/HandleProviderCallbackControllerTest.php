<?php

declare(strict_types=1);

use App\Auth\AuthSession;
use App\Contact\ContactEloquentModel;
use App\EmailCode\Notifications\EmailCodeNotification;
use App\Portal\PortalSession;
use App\SocialAccount\SocialAccountEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialUser;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\withSession;

beforeEach(function (): void {
    config([
        'services.google.client_id' => 'id',
        'services.google.client_secret' => 'secret',
        'services.microsoft.client_id' => 'id',
        'services.microsoft.client_secret' => 'secret',
    ]);
    Notification::fake();
});

function fakeProviderUser(string $provider, array $attributes): void
{
    Socialite::fake($provider, SocialUser::fake(['id' => 'provider-123', 'name' => 'Sam Lee', 'email' => 'sam@acme.test', ...$attributes]));
}

it('signs a linked account straight in', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    SocialAccountEloquentModel::factory()->create(['user_id' => $user->id, 'provider' => 'microsoft', 'provider_user_id' => 'provider-123']);
    fakeProviderUser('microsoft', []);

    get(route('auth.social.callback', 'microsoft'))->assertRedirect(route('dashboard'));

    assertAuthenticatedAs($user);
});

it('starts sign-up with a verified Google address, suggesting the Google name', function (): void {
    fakeProviderUser('google', ['email_verified' => true]);

    get(route('auth.social.callback', 'google'))
        ->assertRedirectToRoute('signup.profile')
        ->assertSessionHas(AuthSession::VERIFIED_EMAIL, 'sam@acme.test')
        ->assertSessionHas(AuthSession::SUGGESTED_NAME, 'Sam Lee');

    Notification::assertNothingSent();
});

it('links a verified Google address to the existing account with that email', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['email' => 'sam@acme.test']);
    fakeProviderUser('google', ['email_verified' => true]);

    get(route('auth.social.callback', 'google'))->assertRedirect(route('dashboard'));

    assertAuthenticatedAs($user);
    expect(SocialAccountEloquentModel::query()->sole()->user_id)->toBe($user->id);
});

it('asks for an emailed code before trusting a Microsoft or unverified address', function (string $provider, array $attributes): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['email' => 'sam@acme.test']);
    fakeProviderUser($provider, $attributes);

    get(route('auth.social.callback', $provider))
        ->assertRedirectToRoute('auth.code')
        ->assertSessionHas(AuthSession::PENDING_EMAIL, 'sam@acme.test');

    assertGuest();
    expect(SocialAccountEloquentModel::query()->exists())->toBeFalse();
    Notification::assertSentOnDemandTimes(EmailCodeNotification::class, 1);
})->with([
    'microsoft' => ['microsoft', []],
    'google without a verified email' => ['google', ['email_verified' => false]],
]);

it('goes back to log in when the provider sign-in fails', function (): void {
    Socialite::shouldReceive('driver->user')->andThrow(new RuntimeException('denied'));

    get(route('auth.social.callback', 'google'))->assertRedirectToRoute('login');

    assertGuest();
});

it('does not handle providers without keys', function (): void {
    config(['services.microsoft.client_id' => null]);

    get(route('auth.social.callback', 'microsoft'))->assertNotFound();
});

it('signs a contact in to the portal with a verified Google address', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['email' => 'sam@acme.test']);
    $contact = ContactEloquentModel::factory()->withPortalAccess()->create(['email' => 'sam@acme.test']);
    fakeProviderUser('google', ['email_verified' => true]);

    withSession([PortalSession::SOCIAL => true])
        ->get(route('auth.social.callback', 'google'))
        ->assertRedirect(route('portal.home'));

    assertAuthenticatedAs($contact, 'portal');
    assertGuest('web');
    expect(SocialAccountEloquentModel::query()->exists())->toBeFalse();
    expect($user->fresh())->not->toBeNull();
});

it('emails a portal code before trusting a Microsoft address for the portal', function (): void {
    ContactEloquentModel::factory()->withPortalAccess()->create(['email' => 'sam@acme.test']);
    fakeProviderUser('microsoft', []);

    withSession([PortalSession::SOCIAL => true])
        ->get(route('auth.social.callback', 'microsoft'))
        ->assertRedirectToRoute('portal.login.code')
        ->assertSessionHas(PortalSession::PENDING_EMAIL, 'sam@acme.test');

    assertGuest('portal');
    Notification::assertSentOnDemand(EmailCodeNotification::class, fn (EmailCodeNotification $notification): bool => $notification->purpose->value === 'portal_sign_in');
});

it('lets signed-in staff sign in to the portal with Google too', function (): void {
    $staff = UserEloquentModel::factory()->withPersonalTeam()->create();
    $contact = ContactEloquentModel::factory()->withPortalAccess()->create(['email' => 'sam@acme.test']);
    fakeProviderUser('google', ['email_verified' => true]);

    actingAs($staff)
        ->withSession([PortalSession::SOCIAL => true])
        ->get(route('auth.social.callback', 'google'))
        ->assertRedirect(route('portal.home'));

    assertAuthenticatedAs($contact, 'portal');
});
