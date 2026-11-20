<?php

declare(strict_types=1);

use App\Article\ArticleClassificationEnum;
use App\Article\ArticleEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('offers live articles the person may read that match the search', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $team = ['team_id' => $owner->current_team_id];
    $router = ArticleEloquentModel::factory()->published()->create([...$team, 'title' => 'Reset a router']);
    ArticleEloquentModel::factory()->create([...$team, 'title' => 'Payroll']);
    ArticleEloquentModel::factory()->archived()->create([...$team, 'title' => 'Old router']);
    ArticleEloquentModel::factory()->classified(ArticleClassificationEnum::Confidential)->create([...$team, 'title' => 'Router passwords']);

    actingAs($member)
        ->getJson(route('article-links.options', ['q' => 'router']))
        ->assertOk()
        ->assertExactJson(['articles' => [['id' => $router->id, 'title' => 'Reset a router', 'summary' => null, 'status' => 'published']]]);
});
