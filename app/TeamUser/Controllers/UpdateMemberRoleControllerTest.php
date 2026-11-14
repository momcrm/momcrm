<?php

declare(strict_types=1);

use App\TeamUser\TeamUserEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

function joinTeam(UserEloquentModel $owner, string $role = 'member'): UserEloquentModel
{
    $user = UserEloquentModel::factory()->create(['current_team_id' => $owner->current_team_id]);
    TeamUserEloquentModel::query()->create(['team_id' => $owner->current_team_id, 'user_id' => $user->id, 'role' => $role]);

    return $user;
}

it('lets the owner make a member an admin', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = joinTeam($owner);

    actingAs($owner)
        ->patch(route('members.update', $member->id), ['role' => 'admin'])
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Role updated.']);

    expect(TeamUserEloquentModel::query()->where('user_id', $member->id)->sole()->role)->toBe('admin');
});

it('does not let admins change roles, or anyone change the owner', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $admin = joinTeam($owner, 'admin');
    $member = joinTeam($owner);

    actingAs($admin)->patch(route('members.update', $member->id), ['role' => 'admin'])->assertForbidden();
    actingAs($owner)->patch(route('members.update', $owner->id), ['role' => 'member'])->assertForbidden();
});

it('rejects the owner role and strangers', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)->patch(route('members.update', joinTeam($owner)->id), ['role' => 'owner'])->assertSessionHasErrors('role');
    actingAs($owner)->patch(route('members.update', UserEloquentModel::factory()->create()->id), ['role' => 'admin'])->assertNotFound();
});
