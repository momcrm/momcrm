<?php

declare(strict_types=1);

use App\Article\ArticleClassificationEnum;
use App\Article\ArticleEloquentModel;
use App\ArticleVersion\ArticleVersionEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('brings back an earlier version as the next one, keeping what came between', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $article = ArticleEloquentModel::factory()->published()->create([
        'team_id' => $user->current_team_id, 'version_major' => 1, 'version_minor' => 1,
        'title' => 'New title', 'body' => '<p>New words.</p>', 'classification' => 'confidential',
    ]);
    $old = ArticleVersionEloquentModel::factory()->of($article)->create(['version_minor' => 0, 'title' => 'Old title', 'body' => '<p>Old words.</p>']);

    actingAs($user)
        ->post(route('articles.versions.restore', [$article->id, $old->id]))
        ->assertRedirectToRoute('articles.show', $article->id);

    expect($article->refresh())
        ->title->toBe('Old title')
        ->body->toBe('<p>Old words.</p>')
        ->classification->toBe('confidential')
        ->version_minor->toBe(2)
        ->change_note->toBe('Brought back version 1.0.')
        ->and(ArticleVersionEloquentModel::query()->withoutGlobalScopes()->where('version_minor', 1)->sole()->title)->toBe('New title');
});

it("returns not found for a version the person can't read", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $article = ArticleEloquentModel::factory()->published()->create(['team_id' => $owner->current_team_id, 'version_major' => 1, 'version_minor' => 1]);
    $old = ArticleVersionEloquentModel::factory()->of($article)->create(['classification' => ArticleClassificationEnum::Confidential->value]);

    actingAs($member)->post(route('articles.versions.restore', [$article->id, $old->id]))->assertNotFound();
});
