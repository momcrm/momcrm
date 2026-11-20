<?php

declare(strict_types=1);

use App\Article\ArticleEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('deletes an article and returns to the list', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $article = ArticleEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)->delete(route('articles.destroy', $article->id))->assertRedirectToRoute('articles.index');

    expect(ArticleEloquentModel::query()->exists())->toBeFalse();
});

it('leaves deleting to owners and admins by default', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $article = ArticleEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($member)->delete(route('articles.destroy', $article->id))->assertForbidden();

    expect(ArticleEloquentModel::query()->exists())->toBeTrue();
});
