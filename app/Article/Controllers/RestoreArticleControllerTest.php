<?php

declare(strict_types=1);

use App\Article\ArticleEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('brings an archived article back', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $article = ArticleEloquentModel::factory()->archived()->create(['team_id' => $user->current_team_id]);

    actingAs($user)->post(route('articles.restore', $article->id))->assertRedirect();

    expect($article->refresh()->archived_at)->toBeNull();
});

it('returns not found for an article that is not archived', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $article = ArticleEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)->post(route('articles.restore', $article->id))->assertNotFound();
});
