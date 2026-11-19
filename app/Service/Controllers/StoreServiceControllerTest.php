<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldTypeEnum;
use App\Rate\RateEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

/**
 * @return array<string, mixed>
 */
function serviceInput(string $rateId, array $overrides = []): array
{
    return [
        'name' => 'Managed IT support',
        'status' => 'active',
        'rate_id' => $rateId,
        'quantity' => 3,
        'billing_starts_on' => '2026-12-01',
        ...$overrides,
    ];
}

it("adds a service on a rate to a customer and returns to the customer's services", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $customer = CustomerEloquentModel::factory()->create($team);
    $contact = ContactEloquentModel::factory()->create([...$team, 'customer_id' => $customer->id]);
    $rate = RateEloquentModel::factory()->create($team);

    actingAs($user)
        ->post(route('services.store', $customer->id), serviceInput($rate->id, ['contact_id' => $contact->id, 'billing_period_in_months' => 12, 'auto_renew' => '1']))
        ->assertRedirectToRoute('customers.services', $customer->id);

    expect(ServiceEloquentModel::query()->sole())
        ->team_id->toBe($user->current_team_id)
        ->customer_id->toBe($customer->id)
        ->rate_id->toBe($rate->id)
        ->contact_id->toBe($contact->id)
        ->name->toBe('Managed IT support')
        ->short_name->toBe('managed-it-support')
        ->quantity->toBe(3)
        ->billing_period_in_months->toBe(12)
        ->billing_starts_on->toDateString()->toBe('2026-12-01')
        ->auto_renew->toBeTrue()
        ->status->toBe('active');
});

it("numbers the short name when the customer already has one, and keeps one that's typed", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $customer = CustomerEloquentModel::factory()->create($team);
    $rate = RateEloquentModel::factory()->create($team);
    ServiceEloquentModel::factory()->create([...$team, 'customer_id' => $customer->id, 'short_name' => 'managed-it-support']);

    actingAs($user)->post(route('services.store', $customer->id), serviceInput($rate->id))->assertSessionHasNoErrors();
    actingAs($user)->post(route('services.store', $customer->id), serviceInput($rate->id, ['short_name' => 'example.com.au']))->assertSessionHasNoErrors();

    expect(ServiceEloquentModel::query()->where('customer_id', $customer->id)->orderBy('short_name')->pluck('short_name')->all())
        ->toBe(['example.com.au', 'managed-it-support', 'managed-it-support-2']);
});

it('checks the fields', function (Closure $input, string $field, string $message): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $customer = CustomerEloquentModel::factory()->create($team);
    $rate = RateEloquentModel::factory()->create($team);
    ServiceEloquentModel::factory()->create([...$team, 'customer_id' => $customer->id, 'short_name' => 'taken']);

    actingAs($user)
        ->post(route('services.store', $customer->id), $input($rate->id, $user->current_team_id))
        ->assertSessionHasErrors([$field => $message]);

    expect(ServiceEloquentModel::query()->count())->toBe(1);
})->with([
    'no name' => [fn (string $rate): array => serviceInput($rate, ['name' => '']), 'name', 'The name field is required.'],
    'no rate' => [fn (string $rate): array => serviceInput($rate, ['rate_id' => null]), 'rate_id', 'The rate id field is required.'],
    "another team's rate" => [fn (): array => serviceInput(RateEloquentModel::factory()->create()->id), 'rate_id', 'Choose one of your rates.'],
    'an archived rate' => [fn (string $rate, string $team): array => serviceInput(RateEloquentModel::factory()->archived()->create(['team_id' => $team])->id), 'rate_id', 'Choose one of your rates.'],
    'a short name already used' => [fn (string $rate): array => serviceInput($rate, ['short_name' => 'taken']), 'short_name', 'This customer already has a service with that short name.'],
    'a short name with spaces' => [fn (string $rate): array => serviceInput($rate, ['short_name' => 'two words']), 'short_name', 'Use only letters, numbers and @ . _ -, as in a domain or email address.'],
    "another customer's contact" => [fn (string $rate): array => serviceInput($rate, ['contact_id' => ContactEloquentModel::factory()->create()->id]), 'contact_id', 'The selected contact id is invalid.'],
    'expiring before billing starts' => [fn (string $rate): array => serviceInput($rate, ['ends_on' => '2026-11-01']), 'ends_on', "It can't expire before billing starts."],
]);

it("returns not found for another team's customer", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $rate = RateEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('services.store', CustomerEloquentModel::factory()->create()->id), serviceInput($rate->id))
        ->assertNotFound();

    expect(ServiceEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
});

it('adds a service with its custom field values, rejecting one of the wrong type', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $customer = CustomerEloquentModel::factory()->create($team);
    $rate = RateEloquentModel::factory()->create($team);
    $text = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Service)->create($team);
    $number = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Service)->ofType(CustomFieldTypeEnum::Number)->create([...$team, 'name' => 'Count']);

    actingAs($user)
        ->post(route('services.store', $customer->id), serviceInput($rate->id, ['custom_fields' => [$number->id => 'many']]))
        ->assertSessionHasErrors(["custom_fields.{$number->id}" => 'The Count field must be a number.']);

    actingAs($user)
        ->post(route('services.store', $customer->id), serviceInput($rate->id, ['custom_fields' => [$text->id => 'North', $number->id => '2']]))
        ->assertSessionHasNoErrors();

    expect(ServiceEloquentModel::query()->withoutGlobalScopes()->sole()->custom_fields)->toEqual([$text->id => 'North', $number->id => 2]);
});
