<?php

declare(strict_types=1);

use App\Article\ArticleClassificationEnum;
use App\Article\ArticleEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

/**
 * @return array<string, string>
 */
function articleForm(array $overrides = []): array
{
    return [
        'title' => 'Reset a router',
        'summary' => 'When the internet drops',
        'body' => '<h2>Steps</h2><p>Hold <strong>reset</strong>.</p>',
        'classification' => 'internal',
        'status' => 'draft',
        ...$overrides,
    ];
}

it('saves the editor, keeping the body as sanitised HTML and plain text', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $article = ArticleEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->from(route('articles.edit', $article->id))
        ->put(route('articles.update', $article->id), articleForm(['body' => '<h2>Steps</h2><p onclick="steal()">Hold <strong>reset</strong>.</p><script>steal()</script>']))
        ->assertRedirectToRoute('articles.edit', $article->id);

    expect($article->refresh())
        ->title->toBe('Reset a router')
        ->summary->toBe('When the internet drops')
        ->body->toBe('<h2>Steps</h2><p>Hold <strong>reset</strong>.</p>')
        ->body_text->toBe("Steps\nHold reset.")
        ->status->toBe('draft')
        ->published_at->toBeNull();
});

it('remembers when an article was first published', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $article = ArticleEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    $this->travelTo('2026-11-20 09:00');
    actingAs($user)->put(route('articles.update', $article->id), articleForm(['status' => 'published']));

    $this->travelTo('2026-11-21 09:00');
    actingAs($user)->put(route('articles.update', $article->id), articleForm(['status' => 'published', 'title' => 'Reset any router']));

    expect($article->refresh())
        ->status->toBe('published')
        ->published_at->toDateTimeString()->toBe('2026-11-20 09:00:00');
});

it('stores an empty body as nothing', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $article = ArticleEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)->put(route('articles.update', $article->id), articleForm(['body' => '<p></p>']));

    expect($article->refresh())->body->toBeNull()->body_text->toBeNull();
});

it("won't let a member raise an article above what they can read", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $article = ArticleEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($member)
        ->put(route('articles.update', $article->id), articleForm(['classification' => 'strictly_confidential']))
        ->assertSessionHasErrors('classification');

    expect($article->refresh()->classification)->toBe('internal');
});

it("returns not found for an article the person can't read", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $article = ArticleEloquentModel::factory()->classified(ArticleClassificationEnum::Confidential)->create(['team_id' => $owner->current_team_id]);

    actingAs($member)->put(route('articles.update', $article->id), articleForm())->assertNotFound();
});
