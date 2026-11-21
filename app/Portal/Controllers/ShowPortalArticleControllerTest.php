<?php

declare(strict_types=1);

use App\Article\ArticleClassificationEnum;
use App\Article\ArticleEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows a customer article, its pictures at the same address staff use', function (): void {
    $contact = portalContact();
    $article = ArticleEloquentModel::factory()->published()->classified(ArticleClassificationEnum::Customer)->create([
        'team_id' => $contact->team_id,
        'body' => '<p>Click here:</p><img src="/articles/a/images/b" alt="">',
    ]);

    actingAs($contact, 'portal')
        ->get(route('articles.show', $article->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal/articles/show')
            ->where('article.body', '<p>Click here:</p><img src="/articles/a/images/b" alt="">'));
});

it("doesn't show an internal article", function (): void {
    $contact = portalContact();
    $article = ArticleEloquentModel::factory()->published()->create(['team_id' => $contact->team_id]);

    actingAs($contact, 'portal')->get(route('articles.show', $article->id))->assertNotFound();
});
