<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\Rate\RateEloquentModel;
use App\Service\Actions\RunServiceInvoices;
use App\Service\ServiceEloquentModel;
use App\Team\TeamEloquentModel;
use Carbon\CarbonImmutable;

use function Pest\Laravel\artisan;

function invoiceRunSetup(): array
{
    $team = TeamEloquentModel::factory()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $team->id, 'name' => 'Acme']);
    $rate = RateEloquentModel::factory()->priced(['AUD' => 3000])->create(['team_id' => $team->id]);

    return [$team, $customer, $rate];
}

it('drafts one invoice per customer, with a line for each period due, priced from the rate', function (): void {
    [$team, $customer, $rate] = invoiceRunSetup();
    $backup = ServiceEloquentModel::factory()->create(['team_id' => $team->id, 'customer_id' => $customer->id, 'rate_id' => $rate->id, 'name' => 'Backup', 'quantity' => 2, 'billing_starts_on' => '2026-11-19']);
    ServiceEloquentModel::factory()->create(['team_id' => $team->id, 'customer_id' => $customer->id, 'rate_id' => $rate->id, 'name' => 'Hosting', 'billing_starts_on' => '2026-12-01']);

    expect(RunServiceInvoices::on(CarbonImmutable::parse('2026-12-02')))->toBe(1);

    $invoice = InvoiceEloquentModel::query()->withoutGlobalScopes()->sole();
    expect($invoice)
        ->customer_id->toBe($customer->id)
        ->status->toBe('draft')
        ->and($invoice->issued_on->toDateString())->toBe('2026-12-02')
        ->and($invoice->due_on->toDateString())->toBe('2026-12-16');

    $lines = InvoiceItemEloquentModel::query()->withoutGlobalScopes()->orderBy('position')->get();
    expect($lines->map(fn (InvoiceItemEloquentModel $line): array => [$line->description, $line->quantity, $line->unit_price_in_cents, $line->rental_starts_on->toDateString(), $line->rental_ends_on->toDateString()])->all())
        ->toBe([
            ['Backup · 19 Nov 2026 – 30 Nov 2026', 2, 1200, '2026-11-19', '2026-11-30'],
            ['Backup · 1 Dec 2026 – 31 Dec 2026', 2, 3000, '2026-12-01', '2026-12-31'],
            ['Hosting · 1 Dec 2026 – 31 Dec 2026', 1, 3000, '2026-12-01', '2026-12-31'],
        ])
        ->and($lines->first()->service_id)->toBe($backup->id);
});

it("bills a customer in their currency, at the rate's price in it, leaving out services with no price in it", function (): void {
    [$team] = invoiceRunSetup();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $team->id, 'currency' => 'USD']);
    $both = RateEloquentModel::factory()->priced(['AUD' => 3000, 'USD' => 2000])->create(['team_id' => $team->id]);
    $audOnly = RateEloquentModel::factory()->priced(['AUD' => 3000])->create(['team_id' => $team->id]);
    ServiceEloquentModel::factory()->create(['team_id' => $team->id, 'customer_id' => $customer->id, 'rate_id' => $both->id, 'name' => 'Backup', 'billing_starts_on' => '2026-12-01']);
    ServiceEloquentModel::factory()->create(['team_id' => $team->id, 'customer_id' => $customer->id, 'rate_id' => $audOnly->id, 'name' => 'Hosting', 'billing_starts_on' => '2026-12-01']);

    expect(RunServiceInvoices::on(CarbonImmutable::parse('2026-12-02')))->toBe(1)
        ->and(InvoiceEloquentModel::query()->withoutGlobalScopes()->sole()->currency)->toBe('USD')
        ->and(InvoiceItemEloquentModel::query()->withoutGlobalScopes()->get(['description', 'unit_price_in_cents'])->toArray())
        ->toBe([['description' => 'Backup · 1 Dec 2026 – 31 Dec 2026', 'unit_price_in_cents' => 2000]]);
});

it('makes nothing new when run again, but bills again what a voided invoice billed', function (): void {
    [$team, $customer, $rate] = invoiceRunSetup();
    ServiceEloquentModel::factory()->create(['team_id' => $team->id, 'customer_id' => $customer->id, 'rate_id' => $rate->id, 'billing_starts_on' => '2026-12-01']);
    $day = CarbonImmutable::parse('2026-12-02');

    RunServiceInvoices::on($day);
    expect(RunServiceInvoices::on($day))->toBe(0);

    InvoiceEloquentModel::query()->withoutGlobalScopes()->update(['status' => 'void']);

    expect(RunServiceInvoices::on($day))->toBe(1)
        ->and(InvoiceEloquentModel::query()->withoutGlobalScopes()->count())->toBe(2);
});

it('leaves out what is not billed', function (Closure $make): void {
    [$team, $customer, $rate] = invoiceRunSetup();
    $make($team, $customer, $rate);

    expect(RunServiceInvoices::on(CarbonImmutable::parse('2026-12-02')))->toBe(0);
})->with([
    'an excluded customer' => [fn ($team, $customer, $rate) => ServiceEloquentModel::factory()->create(['team_id' => $team->id, 'customer_id' => CustomerEloquentModel::factory()->create(['team_id' => $team->id, 'billing_type' => 'excluded'])->id, 'rate_id' => $rate->id, 'billing_starts_on' => '2026-12-01'])],
    'an inactive customer' => [fn ($team, $customer, $rate) => ServiceEloquentModel::factory()->create(['team_id' => $team->id, 'customer_id' => CustomerEloquentModel::factory()->create(['team_id' => $team->id, 'status' => 'inactive'])->id, 'rate_id' => $rate->id, 'billing_starts_on' => '2026-12-01'])],
    'an inactive service' => [fn ($team, $customer, $rate) => ServiceEloquentModel::factory()->inactive()->create(['team_id' => $team->id, 'customer_id' => $customer->id, 'rate_id' => $rate->id, 'billing_starts_on' => '2026-12-01'])],
    'an archived service' => [fn ($team, $customer, $rate) => ServiceEloquentModel::factory()->archived()->create(['team_id' => $team->id, 'customer_id' => $customer->id, 'rate_id' => $rate->id, 'billing_starts_on' => '2026-12-01'])],
    'a service with no rate' => [fn ($team, $customer, $rate) => ServiceEloquentModel::factory()->create(['team_id' => $team->id, 'customer_id' => $customer->id, 'rate_id' => null, 'billing_starts_on' => '2026-12-01'])],
    'a service starting later' => [fn ($team, $customer, $rate) => ServiceEloquentModel::factory()->create(['team_id' => $team->id, 'customer_id' => $customer->id, 'rate_id' => $rate->id, 'billing_starts_on' => '2027-01-01'])],
]);

it('runs from the command for one team on a given day', function (): void {
    [$team, $customer, $rate] = invoiceRunSetup();
    [$other, $otherCustomer, $otherRate] = invoiceRunSetup();
    ServiceEloquentModel::factory()->create(['team_id' => $team->id, 'customer_id' => $customer->id, 'rate_id' => $rate->id, 'billing_starts_on' => '2026-12-01']);
    ServiceEloquentModel::factory()->create(['team_id' => $other->id, 'customer_id' => $otherCustomer->id, 'rate_id' => $otherRate->id, 'billing_starts_on' => '2026-12-01']);

    artisan('services:invoice', ['--date' => '2026-12-02', '--team' => $team->id])
        ->expectsOutputToContain('1 invoice drafted.')
        ->assertSuccessful();

    expect(InvoiceEloquentModel::query()->withoutGlobalScopes()->sole()->team_id)->toBe($team->id);
});

it("dues each draft by its customer's payment terms", function (): void {
    [$team, $customer, $rate] = invoiceRunSetup();
    $team->update(['days_to_pay' => 30]);
    $quick = CustomerEloquentModel::factory()->create(['team_id' => $team->id, 'days_to_pay' => 7]);
    ServiceEloquentModel::factory()->create(['team_id' => $team->id, 'customer_id' => $customer->id, 'rate_id' => $rate->id, 'billing_starts_on' => '2026-12-01']);
    ServiceEloquentModel::factory()->create(['team_id' => $team->id, 'customer_id' => $quick->id, 'rate_id' => $rate->id, 'billing_starts_on' => '2026-12-01']);

    RunServiceInvoices::on(CarbonImmutable::parse('2026-12-02'));

    expect(InvoiceEloquentModel::query()->withoutGlobalScopes()->pluck('due_on', 'customer_id')->map(fn ($due): string => $due->toDateString())->all())
        ->toEqual([$customer->id => '2027-01-01', $quick->id => '2026-12-09']);
});
