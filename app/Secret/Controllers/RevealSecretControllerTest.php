<?php

declare(strict_types=1);

use App\AccessRule\AccessRuleEloquentModel;
use App\AuditLog\AuditLogEloquentModel;
use App\Secret\SecretEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('reveals the values, uncached, and logs who viewed them', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $secret = SecretEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'values' => [['key' => 'password', 'value' => 'hunter2']]]);

    actingAs($member)
        ->postJson(route('secrets.reveal', $secret->id))
        ->assertOk()
        ->assertExactJson(['values' => [['key' => 'password', 'value' => 'hunter2']]])
        ->assertHeader('Cache-Control', 'no-store, private');

    $audit = AuditLogEloquentModel::query()->where('auditable_id', $secret->id)->where('event', 'viewed')->sole();
    expect($audit)
        ->user_id->toBe($member->id)
        ->team_id->toBe($owner->current_team_id)
        ->and($audit->toJson())->not->toContain('hunter2');
});

it('refuses to reveal when the team takes away the default rule', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $secret = SecretEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    AccessRuleEloquentModel::query()->withoutGlobalScopes()->where('team_id', $owner->current_team_id)->where('name', 'Everyone can reveal secrets')->delete();

    actingAs($member)->postJson(route('secrets.reveal', $secret->id))->assertForbidden();

    expect(AuditLogEloquentModel::query()->where('event', 'viewed')->exists())->toBeFalse();
});

it("returns not found for another team's secret", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->postJson(route('secrets.reveal', SecretEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
