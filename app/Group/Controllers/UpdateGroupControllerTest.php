<?php

declare(strict_types=1);

use App\Group\GroupEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('renames a group and replaces its members', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $group = GroupEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Support']);

    actingAs($user)
        ->put(route('groups.update', $group->id), ['name' => 'Service desk', 'color' => 'blue', 'member_ids' => [$user->id]])
        ->assertSessionHasNoErrors();

    expect($group->fresh())->name->toBe('Service desk')->color->toBe('blue')
        ->and($group->members()->pluck('users.id')->all())->toBe([$user->id]);
});

it("returns not found for another team's group", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->put(route('groups.update', GroupEloquentModel::factory()->create()->id), ['name' => 'X', 'color' => 'blue'])
        ->assertNotFound();
});

it('removes every member when none are picked', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $group = GroupEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Support']);
    $group->members()->attach($user->id, ['role' => 'member']);

    actingAs($user)
        ->put(route('groups.update', $group->id), ['name' => 'Support', 'color' => 'teal', 'members_sent' => '1'])
        ->assertSessionHasNoErrors();

    expect($group->members()->count())->toBe(0);
});
