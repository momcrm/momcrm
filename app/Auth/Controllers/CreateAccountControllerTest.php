<?php

declare(strict_types=1);

use App\Auth\AuthSession;
use App\SocialAccount\SocialAccountEloquentModel;
use App\Team\Actions\FindTeam;
use App\TeamUser\Actions\FindTeamUser;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\post;
use function Pest\Laravel\withSession;

it('creates a verified, passwordless account with a personal team and starts onboarding', function (): void {
    withSession([AuthSession::VERIFIED_EMAIL => 'ada@acme.test'])
        ->post(route('signup.profile.store'), ['name' => 'Ada Lovelace'])
        ->assertRedirect(route('dashboard'));

    $user = UserEloquentModel::query()->where('email', 'ada@acme.test')->sole();
    expect($user)
        ->name->toBe('Ada Lovelace')
        ->password->toBeNull()
        ->email_verified_at->not->toBeNull()
        ->onboarded_at->toBeNull();
    $team = FindTeam::byId($user->current_team_id);
    expect($team->getName()->toString())->toBe("Ada Lovelace's Team")
        ->and(FindTeamUser::byTeamAndUser($team->getId()->toString(), $user->id)->getRole()->toEnum())->toBe(TeamRoleEnum::Owner);
    assertAuthenticatedAs($user);
});

it('saves an optional password, hashed', function (): void {
    withSession([AuthSession::VERIFIED_EMAIL => 'ada@acme.test'])
        ->post(route('signup.profile.store'), ['name' => 'Ada', 'password' => 'a-long-passphrase']);

    expect(Hash::check('a-long-passphrase', (string) UserEloquentModel::query()->sole()->password))->toBeTrue();
});

it('announces the new account so listeners such as invitations run', function (): void {
    Event::fake([Registered::class]);

    withSession([AuthSession::VERIFIED_EMAIL => 'ada@acme.test'])->post(route('signup.profile.store'), ['name' => 'Ada']);

    Event::assertDispatched(Registered::class, fn (Registered $event): bool => $event->user->email === 'ada@acme.test');
});

it('links the Google or Microsoft account that started the sign-up', function (): void {
    withSession([
        AuthSession::VERIFIED_EMAIL => 'ada@acme.test',
        AuthSession::PENDING_SOCIAL => ['provider' => 'google', 'provider_user_id' => 'g-123', 'email' => 'ada@acme.test'],
    ])->post(route('signup.profile.store'), ['name' => 'Ada']);

    expect(SocialAccountEloquentModel::query()->sole())
        ->provider->toBe('google')
        ->provider_user_id->toBe('g-123')
        ->user_id->toBe(UserEloquentModel::query()->sole()->id);
});

it('requires a name', function (): void {
    withSession([AuthSession::VERIFIED_EMAIL => 'ada@acme.test'])
        ->post(route('signup.profile.store'), ['name' => ''])
        ->assertSessionHasErrors(['name' => 'Tell us what to call you.']);

    expect(UserEloquentModel::query()->exists())->toBeFalse();
});

it('creates nothing without a proven email', function (): void {
    post(route('signup.profile.store'), ['name' => 'Ada'])->assertRedirectToRoute('signup');

    expect(UserEloquentModel::query()->exists())->toBeFalse();
    assertGuest();
});
