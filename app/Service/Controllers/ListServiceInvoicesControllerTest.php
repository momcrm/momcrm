<?php

declare(strict_types=1);

use App\Invoice\InvoiceEloquentModel;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('lists the invoice lines that billed a service, newest invoice first', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $service = ServiceEloquentModel::factory()->create($team);
    $older = InvoiceEloquentModel::factory()->create([...$team, 'customer_id' => $service->customer_id, 'issued_on' => '2026-10-01']);
    $newer = InvoiceEloquentModel::factory()->create([...$team, 'customer_id' => $service->customer_id, 'issued_on' => '2026-11-01']);
    InvoiceItemEloquentModel::factory()->create([...$team, 'invoice_id' => $older->id, 'service_id' => $service->id, 'description' => 'October']);
    InvoiceItemEloquentModel::factory()->create([...$team, 'invoice_id' => $newer->id, 'service_id' => $service->id, 'description' => 'November', 'quantity' => 2, 'unit_price_in_cents' => 1500]);
    InvoiceItemEloquentModel::factory()->create([...$team, 'invoice_id' => $newer->id, 'description' => 'Something else']);

    actingAs($user)
        ->get(route('services.invoices', $service->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('services/workspace/invoices')
            ->has('lines', 2)
            ->where('lines.0.description', 'November')
            ->where('lines.0.line_total_in_cents', 3000)
            ->where('lines.0.invoice.id', $newer->id)
            ->where('lines.1.description', 'October')
            ->where('workspace.counts.invoices', 2),
        );
});
