<?php

declare(strict_types=1);

use App\AuditLog\AuditLogEloquentModel;
use App\Secret\SecretEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('renames a secret without touching its values or version', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $secret = SecretEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'values' => [['key' => 'password', 'value' => 'old']]]);

    actingAs($user)
        ->put(route('secrets.update', $secret->id), ['title' => 'Renamed', 'description' => 'Main office'])
        ->assertSessionHasNoErrors();

    expect($secret->refresh())
        ->title->toBe('Renamed')
        ->description->toBe('Main office')
        ->version->toBe(1)
        ->values->toBe([['key' => 'password', 'value' => 'old']]);
});

it('raises the version when the values change, and logs only that', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $secret = SecretEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'values' => [['key' => 'password', 'value' => 'old']]]);

    actingAs($user)
        ->put(route('secrets.update', $secret->id), ['title' => $secret->title, 'values' => [['key' => 'password', 'value' => 'brand-new-secret']]])
        ->assertSessionHasNoErrors();

    $audit = AuditLogEloquentModel::query()->where('auditable_id', $secret->id)->where('event', 'updated')->sole();
    expect($secret->refresh())
        ->version->toBe(2)
        ->values->toBe([['key' => 'password', 'value' => 'brand-new-secret']])
        ->and($audit->toJson())->not->toContain('brand-new-secret')->toContain('version');
});

it('keeps the version when the same values are sent again', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $secret = SecretEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'values' => [['key' => 'password', 'value' => 'same']]]);

    actingAs($user)
        ->put(route('secrets.update', $secret->id), ['title' => $secret->title, 'values' => [['key' => 'password', 'value' => 'same']]])
        ->assertSessionHasNoErrors();

    expect($secret->refresh()->version)->toBe(1);
});

it("returns not found for another team's secret", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->put(route('secrets.update', SecretEloquentModel::factory()->create()->id), ['title' => 'Mine'])
        ->assertNotFound();
});
