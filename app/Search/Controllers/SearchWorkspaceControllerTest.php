<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

it('returns the most recent tickets when there is no search term', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Harbour Dental']);
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'number' => 1, 'title' => 'Older']);
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'number' => 2, 'title' => 'Newer']);

    actingAs($user)
        ->getJson(route('search'))
        ->assertOk()
        ->assertJsonCount(2, 'recentTickets')
        ->assertJsonPath('recentTickets.0.title', 'Newer')
        ->assertJsonPath('recentTickets.0.subtitle', 'Harbour Dental')
        ->assertJsonMissingPath('customers');
});

it('finds tickets, customers, contacts and invoices matching the term', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Harbour Dental']);
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'title' => 'Harbour printer jammed']);
    ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'first_name' => 'Harbour', 'last_name' => 'Admin']);

    actingAs($user)
        ->getJson(route('search', ['q' => 'harbour']))
        ->assertOk()
        ->assertJsonPath('tickets.0.title', 'Harbour printer jammed')
        ->assertJsonPath('customers.0.title', 'Harbour Dental')
        ->assertJsonPath('customers.0.url', route('customers.show', $customer->id))
        ->assertJsonPath('contacts.0.title', 'Harbour Admin')
        ->assertJsonCount(0, 'invoices');
});

it('finds an invoice by its number', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'number' => 142]);

    actingAs($user)
        ->getJson(route('search', ['q' => '#142']))
        ->assertJsonPath('invoices.0.title', 'Invoice #142')
        ->assertJsonPath('invoices.0.url', route('invoices.show', $invoice->id));
});

it('never returns another team\'s records', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    CustomerEloquentModel::factory()->create(['name' => 'Harbour Dental']);
    TicketEloquentModel::factory()->create(['title' => 'Harbour printer jammed']);

    actingAs($user)
        ->getJson(route('search', ['q' => 'harbour']))
        ->assertJsonCount(0, 'customers')
        ->assertJsonCount(0, 'tickets');
});

it('requires a signed-in user', function (): void {
    getJson(route('search'))->assertUnauthorized();
});
