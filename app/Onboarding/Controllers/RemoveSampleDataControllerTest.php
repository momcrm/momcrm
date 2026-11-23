<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\Article\ArticleEloquentModel;
use App\Attachment\AttachmentEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Onboarding\Actions\SeedSampleData;
use App\Project\ProjectEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('removes the sample customers and projects and everything under them, keeping real records', function (): void {
    Storage::fake('local');
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

it("removes the sample customers' articles with their pictures, keeping the team's other articles", function (): void {
    Storage::fake('local');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    SeedSampleData::forTeam(teamId: $user->current_team_id, userId: $user->id);
    $image = AttachmentEloquentModel::query()->withoutGlobalScopes()->where('attachable_type', 'article')->sole();
    $real = ArticleEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)->delete(route('sample-data.destroy'));

    expect(ArticleEloquentModel::query()->withoutGlobalScopes()->pluck('id')->all())->toBe([$real->id]);
    $this->assertModelMissing($image);
    Storage::disk('local')->assertMissing($image->path);
});

it("leaves other teams' sample data alone", function (): void {
    Storage::fake('local');
    $other = UserEloquentModel::factory()->withPersonalTeam()->create();
    SeedSampleData::forTeam(teamId: $other->current_team_id, userId: $other->id);

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())->delete(route('sample-data.destroy'));

    expect(CustomerEloquentModel::query()->withoutGlobalScopes()->where('team_id', $other->current_team_id)->count())->toBe(3);
});

it('tells every page whether the team has sample data', function (): void {
    Storage::fake('local');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($user)->get(route('customers.index'))->assertInertia(fn (Assert $page) => $page->where('hasSampleData', false));

    SeedSampleData::forTeam(teamId: $user->current_team_id, userId: $user->id);

    actingAs($user)->get(route('customers.index'))->assertInertia(fn (Assert $page) => $page->where('hasSampleData', true));
});

it('leaves removing the sample data to owners and admins', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();

    actingAs($member)->delete(route('sample-data.destroy'))->assertForbidden();
});
