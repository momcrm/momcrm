<?php

declare(strict_types=1);

use App\Article\ArticleEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('marks an article reviewed today, due again on the day chosen, with no new version', function (): void {
    $this->travelTo('2026-11-20 09:00');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $article = ArticleEloquentModel::factory()->published()->create(['team_id' => $user->current_team_id, 'version_major' => 1, 'next_review_on' => '2026-11-01']);

    actingAs($user)->post(route('articles.review', $article->id), ['next_review_on' => '2027-05-20'])->assertRedirect();

    expect($article->refresh())
        ->last_reviewed_on->toBe('2026-11-20')
        ->next_review_on->toBe('2027-05-20')
        ->version_minor->toBe(0);
});

it('wants the next review after today', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $article = ArticleEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('articles.review', $article->id), ['next_review_on' => now()->toDateString()])
        ->assertSessionHasErrors(['next_review_on' => 'Choose a day after today.']);
});
