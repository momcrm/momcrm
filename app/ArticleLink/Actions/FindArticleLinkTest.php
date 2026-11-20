<?php

declare(strict_types=1);

use App\Article\Article;
use App\Article\ArticleClassificationEnum;
use App\Article\ArticleEloquentModel;
use App\ArticleLink\Actions\FindArticleLink;
use App\ArticleLink\ArticleLinkEloquentModel;
use App\ArticleLink\ArticleLinkTargetEnum;
use App\Asset\AssetEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it("lists a record's articles the person may read", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $team = ['team_id' => $owner->current_team_id];
    $asset = AssetEloquentModel::factory()->create([...$team, 'customer_id' => CustomerEloquentModel::factory()->create($team)->id]);
    $open = ArticleEloquentModel::factory()->create([...$team, 'title' => 'Reset it']);
    $secret = ArticleEloquentModel::factory()->classified(ArticleClassificationEnum::Confidential)->create([...$team, 'title' => 'Its admin login']);
    $link = ArticleLinkEloquentModel::factory()->between($open, ArticleLinkTargetEnum::Asset, $asset)->create();
    ArticleLinkEloquentModel::factory()->between($secret, ArticleLinkTargetEnum::Asset, $asset)->create();

    expect(FindArticleLink::articlesFor(ArticleLinkTargetEnum::Asset, $asset->id, $owner->current_team_id, $member->id))
        ->toBe([['link_id' => $link->id, 'id' => $open->id, 'title' => 'Reset it', 'summary' => null, 'status' => 'draft', 'classification' => 'internal', 'is_archived' => false]])
        ->and(FindArticleLink::articlesFor(ArticleLinkTargetEnum::Asset, $asset->id, $owner->current_team_id, $owner->id))->toHaveCount(2);
});

it('lists the records an article is attached to, by kind, leaving out deleted ones', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $customer = CustomerEloquentModel::factory()->create($team);
    $article = ArticleEloquentModel::factory()->create($team);
    $ticket = TicketEloquentModel::factory()->create([...$team, 'customer_id' => $customer->id, 'number' => 7, 'title' => 'Internet down']);
    $asset = AssetEloquentModel::factory()->create([...$team, 'customer_id' => $customer->id, 'name' => 'Firewall']);
    $gone = AssetEloquentModel::factory()->create([...$team, 'customer_id' => $customer->id]);
    $ticketLink = ArticleLinkEloquentModel::factory()->between($article, ArticleLinkTargetEnum::Ticket, $ticket)->create();
    $assetLink = ArticleLinkEloquentModel::factory()->between($article, ArticleLinkTargetEnum::Asset, $asset)->create();
    ArticleLinkEloquentModel::factory()->between($article, ArticleLinkTargetEnum::Asset, $gone)->create();
    $gone->delete();

    actingAs($user);

    expect(FindArticleLink::recordsFor(Article::fromEloquentModel($article)))->toBe([
        ['kind' => 'Tickets', 'records' => [['link_id' => $ticketLink->id, 'id' => $ticket->id, 'title' => '#7 Internet down', 'url' => route('tickets.show', $ticket->id)]]],
        ['kind' => 'Assets', 'records' => [['link_id' => $assetLink->id, 'id' => $asset->id, 'title' => 'Firewall', 'url' => route('assets.edit', $asset->id)]]],
    ]);
});
