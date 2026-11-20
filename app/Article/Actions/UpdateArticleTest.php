<?php

declare(strict_types=1);

use App\Article\Actions\UpdateArticle;
use App\Article\Article;
use App\Article\ArticleClassificationEnum;
use App\Article\ArticleEloquentModel;
use App\Article\ArticleStatusEnum;
use App\ArticleVersion\ArticleVersionEloquentModel;
use App\User\UserEloquentModel;

function saveArticle(ArticleEloquentModel $article, array $changes, ?string $editedById = null, bool $isMajor = false): Article
{
    $data = Article::fromEloquentModel($article->refresh());

    foreach ($changes as $field => $value) {
        $data->{'set'.ucfirst($field)}($value);
    }

    return UpdateArticle::fromArticle($data, $editedById, $isMajor);
}

it('edits a draft in place, with no version', function (): void {
    $article = ArticleEloquentModel::factory()->create(['title' => 'First']);

    saveArticle($article, ['title' => 'Second', 'body' => '<p>Words</p>']);

    expect($article->refresh())->title->toBe('Second')->version_major->toBe(0)
        ->and(ArticleVersionEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
});

it('makes an article version 1.0 when it is first published, keeping its note', function (): void {
    $user = UserEloquentModel::factory()->create();
    $article = ArticleEloquentModel::factory()->create();

    saveArticle($article, ['status' => ArticleStatusEnum::Published, 'changeNote' => 'Ready'], $user->id);

    expect($article->refresh())
        ->version_major->toBe(1)
        ->version_minor->toBe(0)
        ->change_note->toBe('Ready')
        ->edited_by_id->toBe($user->id)
        ->and(ArticleVersionEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
});

it('keeps the published article as it was, then moves it to the next minor version', function (): void {
    $jane = UserEloquentModel::factory()->create();
    $sam = UserEloquentModel::factory()->create();
    $article = ArticleEloquentModel::factory()->published()->create([
        'title' => 'Reset a router', 'body' => '<p>Hold reset.</p>', 'body_text' => 'Hold reset.',
        'version_major' => 1, 'version_minor' => 0, 'change_note' => 'Ready', 'edited_by_id' => $jane->id,
    ]);

    saveArticle($article, ['body' => '<p>Hold reset for ten seconds.</p>', 'changeNote' => 'Say how long'], $sam->id);

    expect(ArticleVersionEloquentModel::query()->withoutGlobalScopes()->sole())
        ->article_id->toBe($article->id)
        ->version_major->toBe(1)
        ->version_minor->toBe(0)
        ->body->toBe('<p>Hold reset.</p>')
        ->body_text->toBe('Hold reset.')
        ->change_note->toBe('Ready')
        ->edited_by_id->toBe($jane->id)
        ->and($article->refresh())
        ->version_major->toBe(1)
        ->version_minor->toBe(1)
        ->body->toBe('<p>Hold reset for ten seconds.</p>')
        ->change_note->toBe('Say how long')
        ->edited_by_id->toBe($sam->id);
});

it('moves to the next major version when asked', function (): void {
    $article = ArticleEloquentModel::factory()->published()->create(['version_major' => 1, 'version_minor' => 3]);

    saveArticle($article, ['title' => 'Rewritten'], isMajor: true);

    expect($article->refresh())->version_major->toBe(2)->version_minor->toBe(0);
});

it('changes no version when nothing but the status changes', function (): void {
    $article = ArticleEloquentModel::factory()->published()->create(['version_major' => 1, 'version_minor' => 2, 'change_note' => 'Kept']);

    saveArticle($article, ['status' => ArticleStatusEnum::Draft, 'changeNote' => 'Ignored']);

    expect($article->refresh())
        ->status->toBe('draft')
        ->version_minor->toBe(2)
        ->change_note->toBe('Kept')
        ->and(ArticleVersionEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
});

it('keeps versions of an article that has gone back to a draft', function (): void {
    $article = ArticleEloquentModel::factory()->published()->create(['status' => 'draft', 'version_major' => 1, 'version_minor' => 0]);

    saveArticle($article, ['classification' => ArticleClassificationEnum::Confidential]);

    expect($article->refresh())->version_minor->toBe(1)
        ->and(ArticleVersionEloquentModel::query()->withoutGlobalScopes()->sole()->classification)->toBe('internal');
});
