<?php

declare(strict_types=1);

use App\AccessRule\AccessRuleEloquentModel;
use App\Article\ArticleClassificationEnum;
use App\Article\ArticleEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("lists published customer articles for every customer or theirs, never internal ones, drafts or another customer's", function (): void {
    $contact = portalContact();
    $team = ['team_id' => $contact->team_id];
    ArticleEloquentModel::factory()->published()->classified(ArticleClassificationEnum::Customer)->create([...$team, 'title' => 'Resetting your password']);
    ArticleEloquentModel::factory()->published()->classified(ArticleClassificationEnum::Customer)->create([...$team, 'title' => 'Coastal VPN setup', 'customer_id' => $contact->customer_id]);
    ArticleEloquentModel::factory()->published()->classified(ArticleClassificationEnum::Customer)->create([...$team, 'title' => 'Someone else', 'customer_id' => portalContact()->customer_id]);
    ArticleEloquentModel::factory()->published()->create([...$team, 'title' => 'Internal runbook']);
    ArticleEloquentModel::factory()->classified(ArticleClassificationEnum::Customer)->create([...$team, 'title' => 'Draft']);

    actingAs($contact, 'portal')
        ->get(route('portal.articles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal/articles/index')
            ->where('articles', fn ($articles): bool => collect($articles)->pluck('title')->all() === ['Coastal VPN setup', 'Resetting your password'])
            ->where('articles.0.is_for_customer', true));
});

it('searches titles', function (): void {
    $contact = portalContact();
    ArticleEloquentModel::factory()->published()->classified(ArticleClassificationEnum::Customer)->create(['team_id' => $contact->team_id, 'title' => 'Resetting your password']);
    ArticleEloquentModel::factory()->published()->classified(ArticleClassificationEnum::Customer)->create(['team_id' => $contact->team_id, 'title' => 'Printing']);

    actingAs($contact, 'portal')
        ->get(route('portal.articles.index', ['search' => 'pass']))
        ->assertInertia(fn (Assert $page) => $page->has('articles', 1)->where('articles.0.title', 'Resetting your password'));
});

it('is forbidden when no rule lets contacts see articles', function (): void {
    $contact = portalContact();
    AccessRuleEloquentModel::query()->withoutGlobalScopes()->where('team_id', $contact->team_id)->where('audience', 'contact')->delete();

    actingAs($contact, 'portal')->get(route('portal.articles.index'))->assertForbidden();
});
