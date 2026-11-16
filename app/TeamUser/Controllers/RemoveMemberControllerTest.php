<?php

declare(strict_types=1);

use App\Group\GroupEloquentModel;
use App\Project\ProjectEloquentModel;
use App\TeamUser\TeamUserEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketStatusCategoryEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

function addToTeam(UserEloquentModel $owner, string $role = 'member'): UserEloquentModel
{
    $user = UserEloquentModel::factory()->create(['current_team_id' => $owner->current_team_id]);
    TeamUserEloquentModel::query()->create(['team_id' => $owner->current_team_id, 'user_id' => $user->id, 'role' => $role]);

    return $user;
}

it('removes a member from the team, its groups, projects and open tickets', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $owner->current_team_id;
    $member = addToTeam($owner);
    $group = GroupEloquentModel::factory()->create(['team_id' => $teamId]);
    $group->members()->attach($member->id, ['role' => 'member']);
    $project = ProjectEloquentModel::factory()->create(['team_id' => $teamId, 'lead_id' => $member->id]);
    $project->members()->attach($member->id);
    $openTicket = TicketEloquentModel::factory()->create(['team_id' => $teamId, 'assignee_id' => $member->id]);
    $closedTicket = TicketEloquentModel::factory()->create([
        'team_id' => $teamId,
        'assignee_id' => $member->id,
        'status_id' => TicketStatusCategoryEnum::Closed->statusIds()->where('team_id', $teamId)->value('id'),
    ]);

    actingAs($owner)
        ->from(route('members'))
        ->delete(route('members.destroy', $member->id))
        ->assertRedirectToRoute('members')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => "{$member->name} was removed from the team."]);

    expect(TeamUserEloquentModel::query()->where('team_id', $teamId)->where('user_id', $member->id)->exists())->toBeFalse()
        ->and($group->members()->count())->toBe(0)
        ->and($project->members()->count())->toBe(0)
        ->and($project->refresh()->lead_id)->toBeNull()
        ->and($openTicket->refresh()->assignee_id)->toBeNull()
        ->and($closedTicket->refresh()->assignee_id)->toBe($member->id)
        ->and($closedTicket->status_id)->not->toBeNull();
});

it('lets admins remove members but not other admins', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $admin = addToTeam($owner, 'admin');

    actingAs($admin)->delete(route('members.destroy', addToTeam($owner)->id))->assertRedirect();
    actingAs($admin)->delete(route('members.destroy', addToTeam($owner, 'admin')->id))->assertForbidden();
});

it('does not let anyone remove the owner or themselves, or members remove anyone', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $admin = addToTeam($owner, 'admin');
    $member = addToTeam($owner);

    actingAs($admin)->delete(route('members.destroy', $owner->id))->assertForbidden();
    actingAs($admin)->delete(route('members.destroy', $admin->id))->assertForbidden();
    actingAs($member)->delete(route('members.destroy', addToTeam($owner)->id))->assertForbidden();
});

it('returns not found for someone outside the team', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)->delete(route('members.destroy', UserEloquentModel::factory()->create()->id))->assertNotFound();
});

it("goes to the members list when removed from the member's own page", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = addToTeam($owner);

    actingAs($owner)
        ->from(route('members.show', $member->id))
        ->delete(route('members.destroy', $member->id))
        ->assertRedirectToRoute('members');
});
