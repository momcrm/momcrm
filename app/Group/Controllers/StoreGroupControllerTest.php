<?php

declare(strict_types=1);

use App\Group\GroupEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('creates a group with its creator as lead', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    $response = actingAs($user)->post(route('groups.store'), [
        'name' => 'Field techs',
        'color' => 'teal',
        'lead_id' => $user->id,
        'member_ids' => [$user->id],
    ]);

    $group = GroupEloquentModel::query()->sole();
    $response->assertRedirectToRoute('groups.show', $group->id);
    expect($group->team_id)->toBe($user->current_team_id)
        ->and($group->members()->sole()->pivot->role)->toBe('lead');
});

it('needs a name unique in the team', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    GroupEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Support']);

    actingAs($user)
        ->post(route('groups.store'), ['name' => 'Support', 'color' => 'teal'])
        ->assertSessionHasErrors(['name' => 'There is already a group with that name.']);
});
