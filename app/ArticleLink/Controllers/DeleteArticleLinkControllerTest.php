<?php

declare(strict_types=1);

use App\Article\ArticleClassificationEnum;
use App\Article\ArticleEloquentModel;
use App\ArticleLink\ArticleLinkEloquentModel;
use App\ArticleLink\ArticleLinkTargetEnum;
use App\Asset\AssetEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('takes an article off a record', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $asset = AssetEloquentModel::factory()->create([...$team, 'customer_id' => CustomerEloquentModel::factory()->create($team)->id]);
    $link = ArticleLinkEloquentModel::factory()->between(ArticleEloquentModel::factory()->create($team), ArticleLinkTargetEnum::Asset, $asset)->create();

    actingAs($user)->delete(route('article-links.destroy', $link->id))->assertRedirect();

    expect(ArticleLinkEloquentModel::query()->exists())->toBeFalse();
});

it("returns not found for a link to an article the person can't read", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $team = ['team_id' => $owner->current_team_id];
    $asset = AssetEloquentModel::factory()->create([...$team, 'customer_id' => CustomerEloquentModel::factory()->create($team)->id]);
    $article = ArticleEloquentModel::factory()->classified(ArticleClassificationEnum::StrictlyConfidential)->create($team);
    $link = ArticleLinkEloquentModel::factory()->between($article, ArticleLinkTargetEnum::Asset, $asset)->create();

    actingAs($member)->delete(route('article-links.destroy', $link->id))->assertNotFound();

    expect(ArticleLinkEloquentModel::query()->exists())->toBeTrue();
});
