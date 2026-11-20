<?php

declare(strict_types=1);

use App\Article\ArticleClassificationEnum;
use App\Article\ArticleEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

it('shows an article with its body and author', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['name' => 'Jane Doe']);
    $article = ArticleEloquentModel::factory()->published()->create(['team_id' => $user->current_team_id, 'author_id' => $user->id, 'body' => '<p>Turn it off and on.</p>']);

    actingAs($user)
        ->get(route('articles.show', $article->id))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('articles/show')
            ->where('article.body', '<p>Turn it off and on.</p>')
            ->where('article.author_name', 'Jane Doe')
            ->where('article.status', 'published')
            ->where('can.update', true)
            ->where('can.delete', true));
});

it("returns not found for an article above the person's role, or another team's", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $admin = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Admin)->create();
    $secret = ArticleEloquentModel::factory()->classified(ArticleClassificationEnum::StrictlyConfidential)->create(['team_id' => $owner->current_team_id]);

    actingAs($admin)->get(route('articles.show', $secret->id))->assertNotFound();
    actingAs($owner)->get(route('articles.show', $secret->id))->assertOk();
    actingAs($owner)->get(route('articles.show', ArticleEloquentModel::factory()->create()->id))->assertNotFound();
});
