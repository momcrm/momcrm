<?php

declare(strict_types=1);

use App\ArticleCategory\ArticleCategoryEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

it('lists the categories in tree order with their paths', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $networking = ArticleCategoryEloquentModel::factory()->create([...$team, 'name' => 'Networking']);
    ArticleCategoryEloquentModel::factory()->under($networking)->create(['name' => 'Routers']);
    ArticleCategoryEloquentModel::factory()->create([...$team, 'name' => 'Accounts']);

    actingAs($user)
        ->get(route('article-categories.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/knowledge-base')
            ->where('categories', fn ($categories): bool => collect($categories)->map(fn (array $category): string => "{$category['depth']}:{$category['path']}")->all() === ['0:Accounts', '0:Networking', '1:Networking › Routers']));
});
