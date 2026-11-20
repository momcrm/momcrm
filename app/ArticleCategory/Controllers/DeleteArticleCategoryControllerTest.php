<?php

declare(strict_types=1);

use App\Article\ArticleEloquentModel;
use App\ArticleCategory\ArticleCategoryEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('moves what was in a category up a level', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $networking = ArticleCategoryEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $routers = ArticleCategoryEloquentModel::factory()->under($networking)->create();
    $cisco = ArticleCategoryEloquentModel::factory()->under($routers)->create();
    $article = ArticleEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'category_id' => $routers->id]);

    actingAs($user)->delete(route('article-categories.destroy', $routers->id))->assertRedirect();

    expect(ArticleCategoryEloquentModel::query()->find($routers->id))->toBeNull()
        ->and($cisco->refresh()->parent_id)->toBe($networking->id)
        ->and($article->refresh()->category_id)->toBe($networking->id);
});

it('leaves articles of a top category with none', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $networking = ArticleCategoryEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $article = ArticleEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'category_id' => $networking->id]);

    actingAs($user)->delete(route('article-categories.destroy', $networking->id));

    expect($article->refresh()->category_id)->toBeNull();
});
