<?php

declare(strict_types=1);

use App\Article\ArticleEloquentModel;
use App\ArticleCategory\ArticleCategoryEloquentModel;
use App\ArticleLink\ArticleLinkEloquentModel;
use App\Asset\AssetEloquentModel;
use App\Attachment\AttachmentEloquentModel;
use App\Choice\ChoiceEloquentModel;
use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Group\GroupEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\Milestone\MilestoneEloquentModel;
use App\Onboarding\Actions\SeedSampleData;
use App\Payment\PaymentEloquentModel;
use App\PaymentAllocation\PaymentAllocationEloquentModel;
use App\Project\ProjectEloquentModel;
use App\Rate\RateEloquentModel;
use App\Service\ServiceEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\travelTo;

it('adds sample customers with contacts, tickets and a half-paid invoice to the team', function (): void {
    Storage::fake('local');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    SeedSampleData::forTeam(teamId: $user->current_team_id, userId: $user->id);

    $customers = CustomerEloquentModel::query()->withoutGlobalScopes()->orderBy('number')->get();
    expect($customers->pluck('name')->all())->toBe(['Harbour Dental', 'Coastal Logistics', 'Bright Spark Electrical'])
        ->and($customers->every(fn (CustomerEloquentModel $customer): bool => $customer->is_sample && $customer->team_id === $user->current_team_id))->toBeTrue();
    expect(ContactEloquentModel::query()->withoutGlobalScopes()->count())->toBe(4);
    expect(TicketEloquentModel::query()->withoutGlobalScopes()->whereNotNull('customer_id')->whereNull('project_id')->orderBy('number')->pluck('status_id')->all())
        ->toBe([ticketStatusId($user->current_team_id, 'In progress'), ticketStatusId($user->current_team_id, 'Closed')]);
    $invoice = InvoiceEloquentModel::query()->withoutGlobalScopes()->sole();
    expect($invoice)
        ->status->toBe('sent')
        ->customer_id->toBe($customers->first()->id);
    expect(PaymentEloquentModel::query()->withoutGlobalScopes()->sole())
        ->customer_id->toBe($customers->first()->id)
        ->amount_in_cents->toBe(PaymentAllocationEloquentModel::query()->withoutGlobalScopes()->where('invoice_id', $invoice->id)->sole()->amount_in_cents);
});

it('prices the sample services from rates added to the price list, reusing ones it already added', function (): void {
    Storage::fake('local');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    RateEloquentModel::factory()->priced(['AUD' => 9900])->create(['team_id' => $user->current_team_id, 'name' => 'Cloud backup']);

    SeedSampleData::forTeam(teamId: $user->current_team_id, userId: $user->id);

    $rates = RateEloquentModel::query()->withoutGlobalScopes()->orderBy('name')->get();
    expect($rates->pluck('name')->all())->toBe(['Cloud backup', 'Managed IT support', 'Microsoft 365 Business Standard'])
        ->and($rates->firstWhere('name', 'Managed IT support')->prices)->toEqual([['currency' => 'AUD', 'price_in_cents' => 4500, 'cost_in_cents' => null]])
        ->and(ServiceEloquentModel::query()->withoutGlobalScopes()->whereNull('rate_id')->exists())->toBeFalse()
        ->and(ServiceEloquentModel::query()->withoutGlobalScopes()->where('name', 'Cloud backup')->sole()->rate_id)->toBe($rates->first()->id);
});

it('adds sample projects with milestones, labelled tickets with due dates and a group', function (): void {
    Storage::fake('local');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    SeedSampleData::forTeam(teamId: $user->current_team_id, userId: $user->id);

    $projects = ProjectEloquentModel::query()->withoutGlobalScopes()->orderBy('number')->get();
    expect($projects->pluck('name')->all())->toBe(['Harbour Dental office refit', 'Coastal Logistics VPN upgrade', 'Documentation overhaul'])
        ->and($projects->every(fn (ProjectEloquentModel $project): bool => $project->is_sample && $project->lead_id === $user->id))->toBeTrue()
        ->and($projects->last()->customer_id)->toBeNull();
    expect(MilestoneEloquentModel::query()->withoutGlobalScopes()->count())->toBe(4);
    expect(ChoiceEloquentModel::query()->withoutGlobalScopes()->where('list', 'label')->pluck('name')->sort()->values()->all())->toBe(['Billing', 'Hardware', 'Network', 'Onboarding', 'Security', 'Software']);
    expect(GroupEloquentModel::query()->withoutGlobalScopes()->sole()->name)->toBe('Service desk');

    $internal = TicketEloquentModel::query()->withoutGlobalScopes()->where('project_id', $projects->last()->id)->get();
    expect($internal)->toHaveCount(2)
        ->and($internal->every(fn (TicketEloquentModel $ticket): bool => $ticket->customer_id === null && $ticket->due_on !== null))->toBeTrue();
    expect(TicketEloquentModel::query()->withoutGlobalScopes()->where('title', 'Reception printer offline')->sole()->labels->pluck('name')->all())->toBe(['Hardware']);
});

it('reuses the labels, group and article categories when sample data is added again, bringing back a deleted label', function (): void {
    Storage::fake('local');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    ChoiceEloquentModel::query()->withoutGlobalScopes()->where('list', 'label')->where('name', 'Network')->delete();

    SeedSampleData::forTeam(teamId: $user->current_team_id, userId: $user->id);
    SeedSampleData::forTeam(teamId: $user->current_team_id, userId: $user->id);

    expect(ChoiceEloquentModel::query()->withoutGlobalScopes()->where('list', 'label')->count())->toBe(6)
        ->and(GroupEloquentModel::query()->withoutGlobalScopes()->count())->toBe(1)
        ->and(ArticleCategoryEloquentModel::query()->withoutGlobalScopes()->count())->toBe(2);
});

it('adds a published customer article with a diagram, labels and a category, attached to the sample records', function (): void {
    Storage::fake('local');
    travelTo('2026-11-23 09:00:00');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    SeedSampleData::forTeam(teamId: $user->current_team_id, userId: $user->id);

    $article = ArticleEloquentModel::query()->withoutGlobalScopes()->sole();
    expect($article)
        ->team_id->toBe($user->current_team_id)
        ->customer_id->toBe(CustomerEloquentModel::query()->withoutGlobalScopes()->where('name', 'Coastal Logistics')->sole()->id)
        ->author_id->toBe($user->id)
        ->status->toBe('published')
        ->classification->toBe('customer')
        ->next_review_on->toBe('2026-11-18');
    expect($article->labels->pluck('name')->sort()->values()->all())->toBe(['Network', 'Onboarding']);
    expect($article->category)
        ->name->toBe('Remote access')
        ->and($article->category->parent_id)->toBe(ArticleCategoryEloquentModel::query()->withoutGlobalScopes()->where('name', 'Networking')->sole()->id);

    $image = AttachmentEloquentModel::query()->withoutGlobalScopes()->sole();
    expect($image)
        ->attachable_type->toBe('article')
        ->attachable_id->toBe($article->id);
    expect($article->body)->toContain("<img src=\"/articles/{$article->id}/images/{$image->id}\"");
    Storage::disk('local')->assertExists($image->path);

    $links = ArticleLinkEloquentModel::query()->withoutGlobalScopes()->where('article_id', $article->id)->get();
    $tickets = TicketEloquentModel::query()->withoutGlobalScopes()->whereIn('title', ['VPN drops every afternoon', 'Order replacement firewall'])->pluck('id');
    expect($links->where('linkable_type', 'ticket')->pluck('linkable_id')->sort()->values()->all())->toBe($tickets->sort()->values()->all())
        ->and($links->where('linkable_type', 'asset')->pluck('linkable_id')->all())->toBe([AssetEloquentModel::query()->withoutGlobalScopes()->where('name', 'Office firewall')->sole()->id])
        ->and($links->where('linkable_type', 'service')->pluck('linkable_id')->all())->toBe([ServiceEloquentModel::query()->withoutGlobalScopes()->where('name', 'Microsoft 365 Business Standard')->sole()->id]);
});
