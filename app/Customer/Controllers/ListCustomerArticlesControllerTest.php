<?php

declare(strict_types=1);

use App\Article\ArticleClassificationEnum;
use App\Article\ArticleEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

it("lists the customer's articles the person may read, and counts them", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $team = ['team_id' => $owner->current_team_id];
    $customer = CustomerEloquentModel::factory()->create($team);
    ArticleEloquentModel::factory()->create([...$team, 'customer_id' => $customer->id, 'title' => 'Their network']);
    ArticleEloquentModel::factory()->classified(ArticleClassificationEnum::Confidential)->create([...$team, 'customer_id' => $customer->id, 'title' => 'Their admin logins']);
    ArticleEloquentModel::factory()->archived()->create([...$team, 'customer_id' => $customer->id, 'title' => 'Old network']);
    ArticleEloquentModel::factory()->create([...$team, 'title' => 'For everyone']);

    actingAs($member)
        ->get(route('customers.articles', $customer->id))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('customers/workspace/articles')
            ->has('articles.data', 1)
            ->where('articles.data.0.title', 'Their network')
            ->where('workspace.counts.articles', 1));

    actingAs($owner)
        ->get(route('customers.articles', $customer->id))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('articles.data', 2)->where('workspace.counts.articles', 2));
});
