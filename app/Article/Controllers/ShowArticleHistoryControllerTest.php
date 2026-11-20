<?php

declare(strict_types=1);

use App\Article\ArticleClassificationEnum;
use App\Article\ArticleEloquentModel;
use App\ArticleVersion\ArticleVersionEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

it('lists the versions newest first, showing the current one against the one before', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['name' => 'Jane Doe']);
    $article = ArticleEloquentModel::factory()->published()->create([
        'team_id' => $user->current_team_id, 'version_major' => 1, 'version_minor' => 1,
        'body_text' => 'Hold reset for ten seconds.', 'change_note' => 'Say how long', 'edited_by_id' => $user->id,
    ]);
    ArticleVersionEloquentModel::factory()->of($article)->create(['version_minor' => 0, 'body_text' => 'Hold reset.']);

    actingAs($user)
        ->get(route('articles.history', $article->id))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('articles/history')
            ->where('versions.0.label', '1.1')
            ->where('versions.0.edited_by_name', 'Jane Doe')
            ->where('versions.1.label', '1.0')
            ->missing('versions.0.body')
            ->where('selected.change_note', 'Say how long')
            ->where('selected.body_text', 'Hold reset for ten seconds.')
            ->where('previous.body_text', 'Hold reset.'));
});

it('shows an earlier version when asked', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $article = ArticleEloquentModel::factory()->published()->create(['team_id' => $user->current_team_id, 'version_major' => 1, 'version_minor' => 1]);
    $version = ArticleVersionEloquentModel::factory()->of($article)->create(['version_minor' => 0, 'title' => 'Old title']);

    actingAs($user)
        ->get(route('articles.history', [$article->id, 'version' => $version->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('selected.id', $version->id)
            ->where('selected.title', 'Old title')
            ->where('previous', null));
});

it('hides versions that had a classification the person may not read', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $article = ArticleEloquentModel::factory()->published()->create(['team_id' => $owner->current_team_id, 'version_major' => 1, 'version_minor' => 1]);
    ArticleVersionEloquentModel::factory()->of($article)->create(['classification' => ArticleClassificationEnum::StrictlyConfidential->value]);

    actingAs($member)
        ->get(route('articles.history', $article->id))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('versions', 1));

    actingAs($owner)
        ->get(route('articles.history', $article->id))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('versions', 2));
});
