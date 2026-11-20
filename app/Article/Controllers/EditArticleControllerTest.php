<?php

declare(strict_types=1);

use App\Article\ArticleEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

it('opens the editor with the classifications the person may choose', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $admin = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Admin)->create();
    $article = ArticleEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($admin)
        ->get(route('articles.edit', $article->id))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('articles/edit')
            ->where('article.id', $article->id)
            ->where('classifications', fn ($options): bool => collect($options)->pluck('value')->all() === ['customer', 'internal', 'confidential']));
});

it("won't edit an archived article", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $article = ArticleEloquentModel::factory()->archived()->create(['team_id' => $user->current_team_id]);

    actingAs($user)->get(route('articles.edit', $article->id))->assertNotFound();
});
