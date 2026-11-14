<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\Customer\CustomerEloquentModel;
use App\Onboarding\Actions\SeedSampleData;
use App\Project\ProjectEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('removes the sample customers and projects and everything under them, keeping real records', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    SeedSampleData::forTeam(teamId: $user->current_team_id, userId: $user->id);
    $real = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Real customer']);
    $realProject = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->delete(route('sample-data.destroy'))
        ->assertRedirectToRoute('customers.index');

    expect(CustomerEloquentModel::query()->pluck('id')->all())->toBe([$real->id])
        ->and(ProjectEloquentModel::query()->pluck('id')->all())->toBe([$realProject->id])
        ->and(TicketEloquentModel::query()->exists())->toBeFalse();
    expect(ActivityEloquentModel::query()->where('type', ActivityTypeEnum::SampleDataRemoved->value)->sole()->details)->toEqual(['customers' => 3]);
});

it("leaves other teams' sample data alone", function (): void {
    $other = UserEloquentModel::factory()->withPersonalTeam()->create();
    SeedSampleData::forTeam(teamId: $other->current_team_id, userId: $other->id);

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())->delete(route('sample-data.destroy'));

    expect(CustomerEloquentModel::query()->withoutGlobalScopes()->where('team_id', $other->current_team_id)->count())->toBe(3);
});

it('tells every page whether the team has sample data', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($user)->get(route('customers.index'))->assertInertia(fn (Assert $page) => $page->where('hasSampleData', false));

    SeedSampleData::forTeam(teamId: $user->current_team_id, userId: $user->id);

    actingAs($user)->get(route('customers.index'))->assertInertia(fn (Assert $page) => $page->where('hasSampleData', true));
});
