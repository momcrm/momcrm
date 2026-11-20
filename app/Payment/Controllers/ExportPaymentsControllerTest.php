<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Payment\PaymentEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('exports the payments a filter matches as CSV', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Acme IT']);
    PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'number' => 1, 'paid_on' => '2026-11-05', 'amount_in_cents' => 12345, 'method' => 'cash', 'reference' => 'Till 2']);
    PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'number' => 2, 'method' => 'card']);
    PaymentEloquentModel::factory()->create(['method' => 'cash']);

    $csv = actingAs($user)
        ->get(route('payments.export', ['method' => 'cash']))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=utf-8')
        ->streamedContent();

    $rows = array_map(str_getcsv(...), explode("\n", trim($csv)));

    expect($rows)->toHaveCount(2)
        ->and($rows[0])->toBe(['Number', 'Paid on', 'Customer', 'Amount', 'Currency', 'Applied to invoices', 'Not applied', 'Method', 'Reference', 'Notes', 'Recorded at'])
        ->and(array_slice($rows[1], 0, 10))->toBe(['PAY-0001', '2026-11-05', 'Acme IT', '123.45', 'AUD', '0.00', '123.45', 'Cash', 'Till 2', '']);
});
