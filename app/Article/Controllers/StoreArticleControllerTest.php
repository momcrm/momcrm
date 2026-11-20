<?php

declare(strict_types=1);

use App\Article\ArticleEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('starts a draft written by the signed-in person and opens the editor', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    $response = actingAs($user)->post(route('articles.store'), ['title' => '  Reset a router ', 'classification' => 'internal']);

    $article = ArticleEloquentModel::query()->sole();
    $response->assertRedirectToRoute('articles.edit', $article->id);

    expect($article)
        ->team_id->toBe($user->current_team_id)
        ->author_id->toBe($user->id)
        ->title->toBe('Reset a router')
        ->status->toBe('draft')
        ->classification->toBe('internal')
        ->published_at->toBeNull();
});

it("won't let a member give an article a classification they couldn't read", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();

    actingAs($member)
        ->post(route('articles.store'), ['title' => 'Secrets', 'classification' => 'confidential'])
        ->assertSessionHasErrors(['classification' => 'Choose a classification you can read yourself.']);

    expect(ArticleEloquentModel::query()->exists())->toBeFalse();
});

it('requires a title', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('articles.store'), ['classification' => 'internal'])
        ->assertSessionHasErrors(['title' => 'The title field is required.']);
});
