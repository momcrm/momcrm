<?php

declare(strict_types=1);

use App\Article\ArticleClassificationEnum;
use App\Article\ArticleEloquentModel;
use App\ArticleLink\ArticleLinkEloquentModel;
use App\Asset\AssetEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Service\ServiceEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('attaches an article to a ticket, asset or service, once', function (string $target, Closure $record): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $customer = CustomerEloquentModel::factory()->create($team);
    $linkable = $record([...$team, 'customer_id' => $customer->id]);
    $article = ArticleEloquentModel::factory()->published()->create($team);

    actingAs($user)->post(route('article-links.store', [$target, $linkable->id]), ['article_id' => $article->id])->assertRedirect();
    actingAs($user)->post(route('article-links.store', [$target, $linkable->id]), ['article_id' => $article->id]);

    expect(ArticleLinkEloquentModel::query()->sole())
        ->team_id->toBe($user->current_team_id)
        ->article_id->toBe($article->id)
        ->linkable_type->toBe($target)
        ->linkable_id->toBe($linkable->id)
        ->created_by_id->toBe($user->id);
})->with([
    'ticket' => ['ticket', fn (array $attributes) => TicketEloquentModel::factory()->create($attributes)],
    'asset' => ['asset', fn (array $attributes) => AssetEloquentModel::factory()->create($attributes)],
    'service' => ['service', fn (array $attributes) => ServiceEloquentModel::factory()->create($attributes)],
]);

it("won't attach an article the person can't read, or an archived one", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $team = ['team_id' => $owner->current_team_id];
    $ticket = TicketEloquentModel::factory()->create([...$team, 'customer_id' => CustomerEloquentModel::factory()->create($team)->id]);

    foreach ([
        ArticleEloquentModel::factory()->classified(ArticleClassificationEnum::Confidential)->create($team),
        ArticleEloquentModel::factory()->archived()->create($team),
    ] as $article) {
        actingAs($member)
            ->post(route('article-links.store', ['ticket', $ticket->id]), ['article_id' => $article->id])
            ->assertSessionHasErrors(['article_id' => 'Choose an article you can read.']);
    }

    expect(ArticleLinkEloquentModel::query()->exists())->toBeFalse();
});

it("returns not found for another team's record", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $article = ArticleEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('article-links.store', ['asset', AssetEloquentModel::factory()->create()->id]), ['article_id' => $article->id])
        ->assertNotFound();
});
