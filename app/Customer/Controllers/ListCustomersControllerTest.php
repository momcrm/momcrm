<?php

declare(strict_types=1);

use App\Choice\ChoiceEloquentModel;
use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldTypeEnum;
use App\Invoice\InvoiceEloquentModel;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it("lists the current team's customers by name with their contact counts", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $zeta = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Zeta Pty Ltd']);
    CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Acme IT']);
    CustomerEloquentModel::factory()->create(['name' => 'Another team']);
    ContactEloquentModel::factory()->count(2)->create(['team_id' => $user->current_team_id, 'customer_id' => $zeta->id]);

    actingAs($user)
        ->get(route('customers.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/index')
            ->where('customers.total', 2)
            ->where('customers.data.0.name', 'Acme IT')
            ->where('customers.data.1.name', 'Zeta Pty Ltd')
            ->where('customers.data.1.contacts_count', 2),
        );
});

it('searches customers by name or email', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Acme IT', 'email' => 'hi@acme.test']);
    CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Zeta', 'email' => 'ops@zeta.test']);

    actingAs($user)
        ->get(route('customers.index', ['search' => 'zeta.test']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('customers.total', 1)
            ->where('customers.data.0.name', 'Zeta')
            ->where('filters.search', 'zeta.test'),
        );
});

it('requires finishing onboarding', function (): void {
    actingAs(UserEloquentModel::factory()->notOnboarded()->withPersonalTeam()->create())
        ->get(route('customers.index'))
        ->assertRedirectToRoute('onboarding.about');
});

it('requires authentication', function (): void {
    get(route('customers.index'))->assertRedirectToRoute('login');
});

it('filters customers by type, billing type and status, and finds them by prefix', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $business = ChoiceEloquentModel::factory()->customerType()->create(['team_id' => $user->current_team_id]);
    CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Acme IT', 'prefix' => 'ACME', 'type_id' => $business->id, 'billing_type' => 'automatic']);
    CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Zeta', 'status' => 'inactive', 'billing_type' => 'excluded']);

    $names = fn (array $query): array => actingAs($user)->get(route('customers.index', $query))
        ->viewData('page')['props']['customers']['data'];

    expect(array_column($names(['type' => $business->id]), 'name'))->toBe(['Acme IT'])
        ->and(array_column($names(['billing' => 'excluded']), 'name'))->toBe(['Zeta'])
        ->and(array_column($names(['status' => 'inactive']), 'name'))->toBe(['Zeta'])
        ->and(array_column($names(['search' => 'acm']), 'name'))->toBe(['Acme IT']);
});

it('shows what each customer owes on sent invoices and how many services they have', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $invoice = fn (string $status) => InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'status' => $status, 'tax_rate_basis_points' => 1000]);
    foreach (['sent', 'sent', 'draft', 'paid'] as $status) {
        InvoiceItemEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'invoice_id' => $invoice($status)->id, 'quantity' => 2, 'unit_price_in_cents' => 5000]);
    }
    ServiceEloquentModel::factory()->count(3)->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);

    actingAs($user)
        ->get(route('customers.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('customers.data.0.balance_in_cents', 22000)
            ->where('customers.data.0.services_count', 3),
        );
});

it('filters customers by a custom field, ignoring a filter on a ticket field', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $teamId = $user->current_team_id;
    $vip = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Customer)->ofType(CustomFieldTypeEnum::Checkbox)->create(['team_id' => $teamId]);
    $ticketField = CustomFieldEloquentModel::factory()->ofType(CustomFieldTypeEnum::Checkbox)->create(['team_id' => $teamId]);
    CustomerEloquentModel::factory()->create(['team_id' => $teamId, 'name' => 'Important', 'custom_fields' => [$vip->id => true]]);
    CustomerEloquentModel::factory()->create(['team_id' => $teamId, 'name' => 'Ordinary']);

    actingAs($user)
        ->get(route('customers.index', ['cf' => [$vip->id => '1', $ticketField->id => '1']]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.custom_fields', [$vip->id => '1'])
            ->where('customers.total', 1)
            ->where('customers.data.0.name', 'Important')
            ->where('customers.data.0.custom_field_labels', []),
        );
});
