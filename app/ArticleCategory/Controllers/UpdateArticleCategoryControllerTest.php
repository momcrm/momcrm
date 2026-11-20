<?php

declare(strict_types=1);

use App\ArticleCategory\ArticleCategoryEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('renames a category and moves it', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $networking = ArticleCategoryEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $category = ArticleCategoryEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Routes']);

    actingAs($user)->put(route('article-categories.update', $category->id), ['name' => 'Routers', 'parent_id' => $networking->id])->assertRedirect();

    expect($category->refresh())->name->toBe('Routers')->parent_id->toBe($networking->id);
});

it("won't put a category inside itself or one under it", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $networking = ArticleCategoryEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $routers = ArticleCategoryEloquentModel::factory()->under($networking)->create();
    $cisco = ArticleCategoryEloquentModel::factory()->under($routers)->create();

    foreach ([$networking, $cisco] as $parent) {
        actingAs($user)
            ->put(route('article-categories.update', $networking->id), ['name' => $networking->name, 'parent_id' => $parent->id])
            ->assertSessionHasErrors(['parent_id' => "A category can't go inside itself or a category under it."]);
    }

    expect($networking->refresh()->parent_id)->toBeNull();
});
