<?php

declare(strict_types=1);

use App\ArticleCategory\ArticleCategoryEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('adds a category, under another if asked', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $networking = ArticleCategoryEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Networking']);

    actingAs($user)->post(route('article-categories.store'), ['name' => ' Routers ', 'parent_id' => $networking->id])->assertRedirect();

    expect(ArticleCategoryEloquentModel::query()->where('name', 'Routers')->sole())
        ->team_id->toBe($user->current_team_id)
        ->parent_id->toBe($networking->id);
});

it('keeps names unique beside each other, whatever the case', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $networking = ArticleCategoryEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Networking']);
    ArticleCategoryEloquentModel::factory()->under($networking)->create(['name' => 'Routers']);

    actingAs($user)
        ->post(route('article-categories.store'), ['name' => 'routers', 'parent_id' => $networking->id])
        ->assertSessionHasErrors(['name' => 'There is already a category with that name there.']);

    actingAs($user)->post(route('article-categories.store'), ['name' => 'Routers'])->assertSessionDoesntHaveErrors();
});

it("won't file a category under another team's", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($user)
        ->post(route('article-categories.store'), ['name' => 'Routers', 'parent_id' => ArticleCategoryEloquentModel::factory()->create()->id])
        ->assertSessionHasErrors(['parent_id' => 'Choose one of your categories.']);
});

it('leaves categories to owners and admins', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();

    actingAs($member)->post(route('article-categories.store'), ['name' => 'Routers'])->assertForbidden();
});
