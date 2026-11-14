<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\Customer\CustomerEloquentModel;
use App\Project\ProjectEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('creates a project led by its creator, numbered within the team', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    $response = actingAs($user)->post(route('projects.store'), [
        'name' => 'Office refit',
        'color' => 'teal',
        'status' => 'planning',
        'visibility' => 'open',
        'customer_id' => $customer->id,
        'starts_on' => '2026-12-01',
        'due_on' => '2026-12-20',
    ]);

    $project = ProjectEloquentModel::query()->sole();
    $response->assertRedirectToRoute('projects.show', $project->id)
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Office refit created.']);
    expect($project)
        ->number->toBe(1)
        ->lead_id->toBe($user->id)
        ->customer_id->toBe($customer->id)
        ->and($project->members->pluck('id')->all())->toBe([$user->id])
        ->and(ActivityEloquentModel::query()->where('type', ActivityTypeEnum::ProjectCreated->value)->sole()->subject_id)->toBe($project->id);
});

it('rejects another team’s customer and members', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($user)
        ->post(route('projects.store'), [
            'name' => 'X', 'color' => 'teal', 'status' => 'planning', 'visibility' => 'open',
            'customer_id' => CustomerEloquentModel::factory()->create()->id,
            'member_ids' => [UserEloquentModel::factory()->create()->id],
        ])
        ->assertSessionHasErrors(['customer_id' => 'Choose one of your customers.', 'member_ids.0' => 'Choose people in your team.']);
});

it('requires a name and a known status and colour', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('projects.store'), ['name' => '', 'color' => 'pink', 'status' => 'done', 'visibility' => 'open'])
        ->assertSessionHasErrors(['name', 'color', 'status']);
});
