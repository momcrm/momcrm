<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Label\LabelEloquentModel;
use App\Project\ProjectEloquentModel;
use App\TeamUser\TeamUserEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

it("returns the team's choices for the new-ticket dialog", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['name' => 'Jane Smith']);
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Acme IT']);
    $label = LabelEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Bug']);
    CustomerEloquentModel::factory()->create();
    LabelEloquentModel::factory()->create();

    actingAs($user)
        ->getJson(route('tickets.form-options'))
        ->assertOk()
        ->assertJsonPath('customers', [['id' => $customer->id, 'name' => 'Acme IT']])
        ->assertJsonPath('members', [['id' => $user->id, 'name' => 'Jane Smith']])
        ->assertJsonPath('labels', [['id' => $label->id, 'name' => 'Bug', 'color' => $label->color]]);
});

it('needs a signed-in person', function (): void {
    getJson(route('tickets.form-options'))->assertUnauthorized();
});

it('leaves members-only projects the person is not in out of the project picker', function (): void {
    $member = UserEloquentModel::factory()->withPersonalTeam()->create();
    $outsider = UserEloquentModel::factory()->create(['current_team_id' => $member->current_team_id]);
    TeamUserEloquentModel::query()->create(['team_id' => $member->current_team_id, 'user_id' => $outsider->id, 'role' => 'member']);
    $restricted = ProjectEloquentModel::factory()->create(['team_id' => $member->current_team_id, 'visibility' => 'restricted', 'name' => 'Secret']);
    $restricted->members()->attach($member->id);
    ProjectEloquentModel::factory()->create(['team_id' => $member->current_team_id, 'visibility' => 'open', 'name' => 'Open']);

    expect(collect(actingAs($outsider)->getJson(route('tickets.form-options'))->json('projects'))->pluck('name')->sort()->values()->all())->toBe(['Open'])
        ->and(collect(actingAs($member)->getJson(route('tickets.form-options'))->json('projects'))->pluck('name')->sort()->values()->all())->toBe(['Open', 'Secret']);
});
