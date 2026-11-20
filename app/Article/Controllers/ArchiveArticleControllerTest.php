<?php

declare(strict_types=1);

use App\Article\ArticleEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('archives an article', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $article = ArticleEloquentModel::factory()->published()->create(['team_id' => $user->current_team_id]);

    actingAs($user)->post(route('articles.archive', $article->id))->assertRedirect();

    expect($article->refresh())->archived_at->not->toBeNull()->status->toBe('published');
});

it('leaves archiving to owners and admins by default', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $article = ArticleEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($member)->post(route('articles.archive', $article->id))->assertForbidden();

    expect($article->refresh()->archived_at)->toBeNull();
});
