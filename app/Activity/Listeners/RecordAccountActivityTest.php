<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\Passkey\PasskeyEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Laravel\Fortify\Features;
use Laravel\Passkeys\Events\PasskeyDeleted;
use Laravel\Passkeys\Events\PasskeyRegistered;

use function Pest\Laravel\post;

/**
 * @return array<int, string>
 */
function recordedActivityTypes(UserEloquentModel $user): array
{
    return ActivityEloquentModel::query()
        ->where('user_id', $user->id)
        ->pluck('type')
        ->all();
}

it('records an account activity for each security event', function (Closure $event, ActivityTypeEnum $type): void {
    $user = UserEloquentModel::factory()->create();

    event($event($user));

    $activity = ActivityEloquentModel::query()->where('user_id', $user->id)->sole();

    expect($activity->type)->toBe($type->value)
        ->and($activity->team_id)->toBeNull();
})->with([
    'sign out' => [fn (UserEloquentModel $user) => new Logout('web', $user), ActivityTypeEnum::SignedOut],
    'password reset' => [fn (UserEloquentModel $user) => new PasswordReset($user), ActivityTypeEnum::PasswordReset],
    'email verified' => [fn (UserEloquentModel $user) => new Verified($user), ActivityTypeEnum::EmailVerified],
    'registered' => [fn (UserEloquentModel $user) => new Registered($user), ActivityTypeEnum::Registered],
    'two-factor confirmed' => [fn (UserEloquentModel $user) => new TwoFactorAuthenticationConfirmed($user), ActivityTypeEnum::TwoFactorEnabled],
    'two-factor disabled' => [fn (UserEloquentModel $user) => new TwoFactorAuthenticationDisabled($user), ActivityTypeEnum::TwoFactorDisabled],
    'recovery codes' => [fn (UserEloquentModel $user) => new RecoveryCodesGenerated($user), ActivityTypeEnum::RecoveryCodesRegenerated],
    'passkey added' => [fn (UserEloquentModel $user) => new PasskeyRegistered($user, new PasskeyEloquentModel), ActivityTypeEnum::PasskeyRegistered],
    'passkey removed' => [fn (UserEloquentModel $user) => new PasskeyDeleted($user, new PasskeyEloquentModel), ActivityTypeEnum::PasskeyDeleted],
]);

it('records a sign-in through the login form', function (): void {
    $user = UserEloquentModel::factory()->create();

    post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    expect(recordedActivityTypes($user))->toBe([ActivityTypeEnum::SignedIn->value]);
});

it('records nothing for a failed sign-in', function (): void {
    $user = UserEloquentModel::factory()->create();

    post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password']);

    expect(recordedActivityTypes($user))->toBe([]);
});

it('waits for confirmation before recording two-factor as enabled', function (bool $confirmationRequired, array $expected): void {
    Features::twoFactorAuthentication(['confirm' => $confirmationRequired]);
    $user = UserEloquentModel::factory()->create();

    event(new TwoFactorAuthenticationEnabled($user));

    expect(recordedActivityTypes($user))->toBe($expected);
})->with([
    'confirmation required' => [true, []],
    'no confirmation' => [false, [ActivityTypeEnum::TwoFactorEnabled->value]],
]);
