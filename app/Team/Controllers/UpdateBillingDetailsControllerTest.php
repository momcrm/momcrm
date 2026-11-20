<?php

declare(strict_types=1);

use App\Team\TeamEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('saves the details printed on invoices', function (): void {
    $team = TeamEloquentModel::factory()->create();
    $admin = UserEloquentModel::factory()->inTeam($team->id, TeamRoleEnum::Admin)->create();

    actingAs($admin)
        ->patch(route('billing.update'), [
            'billing_name' => 'Cebugle Pty Ltd',
            'tax_id_label' => 'ABN',
            'tax_id' => '12 345 678 901',
            'billing_email' => 'accounts@cebugle.example',
            'billing_phone' => '08 9000 0000',
            'billing_address' => "1 Hay St\nPerth WA 6000",
            'payment_details' => 'BSB 000-000, account 12345678',
            'bpay_biller_code' => '123456',
            'invoice_prefix' => 'CEB-',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirectToRoute('billing.edit');

    expect($team->refresh())
        ->billing_name->toBe('Cebugle Pty Ltd')
        ->tax_id_label->toBe('ABN')
        ->tax_id->toBe('12 345 678 901')
        ->billing_email->toBe('accounts@cebugle.example')
        ->billing_phone->toBe('08 9000 0000')
        ->billing_address->toBe("1 Hay St\nPerth WA 6000")
        ->payment_details->toBe('BSB 000-000, account 12345678')
        ->bpay_biller_code->toBe('123456')
        ->invoice_prefix->toBe('CEB-');
});

it('numbers invoices plainly when the prefix is left blank, and refuses spaces in it', function (string $prefix, ?string $error, string $stored): void {
    $team = TeamEloquentModel::factory()->create();
    $admin = UserEloquentModel::factory()->inTeam($team->id, TeamRoleEnum::Admin)->create();

    $response = actingAs($admin)->patch(route('billing.update'), ['invoice_prefix' => $prefix]);

    $error === null ? $response->assertSessionHasNoErrors() : $response->assertSessionHasErrors(['invoice_prefix' => $error]);
    expect($team->refresh()->invoice_prefix)->toBe($stored);
})->with([
    'blank' => ['', null, ''],
    'with a space' => ['INV 2026', 'Use letters, numbers and - / _ . # only, with no spaces.', 'INV-'],
]);

it('clears details left blank', function (): void {
    $team = TeamEloquentModel::factory()->create(['billing_name' => 'Old Name Pty Ltd', 'tax_id_label' => 'VAT', 'tax_id' => 'GB123456789']);
    $owner = UserEloquentModel::query()->findOrFail($team->owner_id);
    $owner->forceFill(['current_team_id' => $team->id])->save();

    actingAs($owner)
        ->patch(route('billing.update'), ['billing_name' => '', 'tax_id_label' => '', 'tax_id' => ''])
        ->assertSessionHasNoErrors();

    expect($team->refresh())
        ->billing_name->toBeNull()
        ->tax_id_label->toBeNull()
        ->tax_id->toBeNull();
});

it('asks what the tax ID is called', function (): void {
    $team = TeamEloquentModel::factory()->create();

    actingAs(UserEloquentModel::factory()->inTeam($team->id, TeamRoleEnum::Admin)->create())
        ->patch(route('billing.update'), ['tax_id' => '12 345 678 901'])
        ->assertSessionHasErrors(['tax_id_label' => 'Say what your tax ID is called, e.g. ABN or VAT.']);
});

it('forbids members from changing the billing details', function (): void {
    $team = TeamEloquentModel::factory()->create();

    actingAs(UserEloquentModel::factory()->inTeam($team->id)->create())
        ->patch(route('billing.update'), ['tax_id_label' => 'ABN', 'tax_id' => '1'])
        ->assertForbidden();

    expect($team->refresh()->tax_id)->toBeNull();
});

it('refuses a BPAY biller code that is not 3 to 10 digits', function (string $code): void {
    $team = TeamEloquentModel::factory()->create();
    $admin = UserEloquentModel::factory()->inTeam($team->id, TeamRoleEnum::Admin)->create();

    actingAs($admin)
        ->patch(route('billing.update'), ['bpay_biller_code' => $code])
        ->assertSessionHasErrors(['bpay_biller_code' => 'A BPAY biller code is 3 to 10 digits.']);
})->with(['12', '12345678901', '12-345']);
