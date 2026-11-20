<?php

declare(strict_types=1);

use App\Article\ArticleClassificationEnum;
use App\Article\ArticleEloquentModel;
use App\ArticleCategory\ArticleCategoryEloquentModel;
use App\Choice\ChoiceEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

it('lists the articles each role may read', function (TeamRoleEnum $role, array $titles): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $user = $role === TeamRoleEnum::Owner ? $owner : UserEloquentModel::factory()->inTeam($owner->current_team_id, $role)->create();
    $team = ['team_id' => $owner->current_team_id];
    ArticleEloquentModel::factory()->create([...$team, 'title' => 'Internal']);
    ArticleEloquentModel::factory()->classified(ArticleClassificationEnum::Confidential)->create([...$team, 'title' => 'Confidential']);
    ArticleEloquentModel::factory()->classified(ArticleClassificationEnum::StrictlyConfidential)->create([...$team, 'title' => 'Strictly confidential']);
    ArticleEloquentModel::factory()->create(['title' => 'Another team']);

    actingAs($user)
        ->get(route('articles.index', ['sort' => 'title', 'direction' => 'asc']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('articles/index')
            ->where('articles.data', fn ($articles): bool => collect($articles)->pluck('title')->all() === $titles)
            ->where('classifications', fn ($options): bool => count($options) === count($titles)));
})->with([
    'member' => [TeamRoleEnum::Member, ['Internal']],
    'admin' => [TeamRoleEnum::Admin, ['Confidential', 'Internal']],
    'owner' => [TeamRoleEnum::Owner, ['Confidential', 'Internal', 'Strictly confidential']],
]);

it('searches titles, summaries and bodies, and filters by status and classification', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $owner->current_team_id];
    ArticleEloquentModel::factory()->published()->create([...$team, 'title' => 'VPN setup', 'body_text' => 'Install the client']);
    ArticleEloquentModel::factory()->create([...$team, 'title' => 'Printers', 'body_text' => 'Install the driver']);
    ArticleEloquentModel::factory()->published()->classified(ArticleClassificationEnum::Confidential)->create([...$team, 'title' => 'Firewall']);

    $titles = fn (array $query): array => actingAs($owner)->get(route('articles.index', $query))
        ->viewData('page')['props']['articles']['data'];

    expect(collect($titles(['search' => 'install']))->pluck('title')->sort()->values()->all())->toBe(['Printers', 'VPN setup'])
        ->and(collect($titles(['status' => 'published']))->pluck('title')->sort()->values()->all())->toBe(['Firewall', 'VPN setup'])
        ->and(collect($titles(['classification' => 'confidential']))->pluck('title')->all())->toBe(['Firewall']);
});

it('shows archived articles only under the archived filter', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    ArticleEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'title' => 'Live']);
    ArticleEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id, 'title' => 'Old']);

    actingAs($owner)->get(route('articles.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('articles.data', 1)->where('articles.data.0.title', 'Live'));

    actingAs($owner)->get(route('articles.index', ['status' => 'archived']))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('articles.data', 1)->where('articles.data.0.title', 'Old'));
});

it('filters by a category and the ones under it, by label and by review due', function (): void {
    $this->travelTo('2026-11-20 09:00');
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $owner->current_team_id];
    $networking = ArticleCategoryEloquentModel::factory()->create($team);
    $routers = ArticleCategoryEloquentModel::factory()->under($networking)->create();
    $label = ChoiceEloquentModel::factory()->label()->create($team);
    ArticleEloquentModel::factory()->create([...$team, 'title' => 'Networking basics', 'category_id' => $networking->id]);
    ArticleEloquentModel::factory()->create([...$team, 'title' => 'Router reset', 'category_id' => $routers->id, 'next_review_on' => '2026-11-20'])->labels()->attach($label->id);
    ArticleEloquentModel::factory()->create([...$team, 'title' => 'Payroll', 'next_review_on' => '2026-12-01']);

    $titles = fn (array $query): array => collect(actingAs($owner)->get(route('articles.index', [...$query, 'sort' => 'title', 'direction' => 'asc']))
        ->viewData('page')['props']['articles']['data'])->pluck('title')->all();

    expect($titles(['category' => $networking->id]))->toBe(['Networking basics', 'Router reset'])
        ->and($titles(['category' => $routers->id]))->toBe(['Router reset'])
        ->and($titles(['label' => $label->id]))->toBe(['Router reset'])
        ->and($titles(['review' => 'due']))->toBe(['Router reset']);
});
