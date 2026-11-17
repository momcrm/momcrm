<?php

declare(strict_types=1);

use App\Attachment\AttachmentEloquentModel;
use App\Contract\ContractEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomObject\CustomObjectEloquentModel;
use App\Record\RecordEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("lists the customer's contracts with their documents", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $contract = ContractEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'title' => 'Managed services']);
    AttachmentEloquentModel::factory()->on($contract)->create(['name' => 'signed.pdf']);
    ContractEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'title' => 'Someone else']);

    actingAs($user)
        ->get(route('customers.contracts', $customer->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/workspace/contracts')
            ->has('contracts', 1)
            ->where('contracts.0.title', 'Managed services')
            ->where('contracts.0.documents.0.name', 'signed.pdf')
            ->where('workspace.counts.contracts', 1),
        );
});

it('filters by status and type, and sorts by end date with undated ones last', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $contract = fn (array $attributes) => ContractEloquentModel::factory()->create([...$attributes, 'team_id' => $user->current_team_id, 'customer_id' => $customer->id]);
    $contract(['title' => 'Later', 'type' => 'msa', 'status' => 'active', 'ends_on' => '2027-06-01']);
    $contract(['title' => 'Sooner', 'type' => 'sla', 'status' => 'active', 'ends_on' => '2027-01-01']);
    $contract(['title' => 'Open-ended', 'type' => 'nda', 'status' => 'draft', 'ends_on' => null]);

    $titles = fn (array $query): array => array_column(
        actingAs($user)->get(route('customers.contracts', ['customer' => $customer->id, ...$query]))->viewData('page')['props']['contracts'],
        'title',
    );

    expect($titles(['sort' => 'ends_on']))->toBe(['Sooner', 'Later', 'Open-ended'])
        ->and($titles(['sort' => 'ends_on', 'direction' => 'desc']))->toBe(['Later', 'Sooner', 'Open-ended'])
        ->and($titles(['status' => 'draft']))->toBe(['Open-ended'])
        ->and($titles(['type' => 'sla']))->toBe(['Sooner']);
});

it("returns not found for another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('customers.contracts', CustomerEloquentModel::factory()->create()->id))
        ->assertNotFound();
});

it('sends each contract what links to it and the names its fields link to, and opens the one named in the query', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $team = ['team_id' => $user->current_team_id];
    $customer = CustomerEloquentModel::factory()->create([...$team, 'name' => 'Acme']);
    $signer = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Contract)->linkingTo(CustomFieldRelationEnum::Customer)->create($team);
    $contract = ContractEloquentModel::factory()->create([...$team, 'customer_id' => $customer->id, 'title' => 'A first', 'custom_fields' => [$signer->id => $customer->id]]);
    ContractEloquentModel::factory()->create([...$team, 'customer_id' => $customer->id, 'title' => 'B second']);
    $vehicles = CustomObjectEloquentModel::factory()->create([...$team, 'name_plural' => 'Vehicles']);
    $link = CustomFieldEloquentModel::factory()->forObject($vehicles)->linkingTo(CustomFieldRelationEnum::Contract)->create(['name' => 'Lease']);
    RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Van', 'custom_fields' => [$link->id => $contract->id]]);

    Pest\Laravel\actingAs($user)
        ->get(route('customers.contracts', ['customer' => $customer->id, 'contract' => $contract->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('open', $contract->id)
            ->where('customFieldLabels', [$customer->id => 'Acme'])
            ->where('contracts.0.linked_records.0.source', 'Vehicles')
            ->where('contracts.0.linked_records.0.field', 'Lease')
            ->where('contracts.0.linked_records.0.records.0.title', 'Van')
            ->where('contracts.1.linked_records', []),
        );
});
