<?php

declare(strict_types=1);

use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function (): void {
    Route::middleware('web')->get('/_context-probe', fn () => Context::only(['request_id', 'user_id', 'team_id']));
});

it('returns a new request id when none is sent', function (): void {
    $response = get('/_context-probe');

    expect(Str::isUuid($response->headers->get('X-Request-Id')))->toBeTrue()
        ->and($response->json('request_id'))->toBe($response->headers->get('X-Request-Id'));
});

it('reuses a valid incoming request id', function (): void {
    $requestId = Str::uuid7()->toString();

    get('/_context-probe', ['X-Request-Id' => $requestId])
        ->assertHeader('X-Request-Id', $requestId)
        ->assertJsonPath('request_id', $requestId);
});

it('ignores an incoming request id that is not a uuid', function (): void {
    $response = get('/_context-probe', ['X-Request-Id' => 'not-a-uuid']);

    expect($response->headers->get('X-Request-Id'))->not->toBe('not-a-uuid')
        ->and(Str::isUuid($response->headers->get('X-Request-Id')))->toBeTrue();
});

it('adds the user and their current team to the context', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($user)
        ->get('/_context-probe')
        ->assertJsonPath('user_id', $user->id)
        ->assertJsonPath('team_id', $user->current_team_id);
});
