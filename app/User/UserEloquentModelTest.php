<?php

declare(strict_types=1);

use App\User\UserEloquentModel;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\withCookie;

it('keeps passwordless people logged in through the remember-me cookie after their session ends', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['password' => null, 'remember_token' => 'remember-me']);

    /** @var SessionGuard $guard */
    $guard = Auth::guard();
    $cookie = "{$user->getKey()}|remember-me|{$guard->hashPasswordForCookie($user->getAuthPassword())}";

    withCookie($guard->getRecallerName(), $cookie)->get(route('dashboard'))->assertOk();

    assertAuthenticatedAs($user);
});

it('does not let passwordless people log in with an empty password', function (): void {
    $user = UserEloquentModel::factory()->create(['password' => null]);

    expect(Auth::validate(['email' => $user->email, 'password' => '']))->toBeFalse()
        ->and(Auth::validate(['email' => $user->email, 'password' => 'anything']))->toBeFalse();
});
