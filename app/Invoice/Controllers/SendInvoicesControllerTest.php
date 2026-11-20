<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceStatusEnum;
use App\Invoice\Notifications\InvoiceNotification;
use App\User\UserEloquentModel;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

it('emails each invoice to the contacts who get invoices, else the primary contact, and names customers with nobody to send to', function (): void {
    Notification::fake();
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $withBilling = CustomerEloquentModel::factory()->create($team);
    $withPrimary = CustomerEloquentModel::factory()->create($team);
    $withNobody = CustomerEloquentModel::factory()->create([...$team, 'name' => 'Quiet Co']);
    ContactEloquentModel::factory()->create([...$team, 'customer_id' => $withBilling->id, 'email' => 'accounts@billing.example', 'receives_invoices' => true]);
    ContactEloquentModel::factory()->create([...$team, 'customer_id' => $withBilling->id, 'email' => 'boss@billing.example', 'is_primary' => true]);
    ContactEloquentModel::factory()->create([...$team, 'customer_id' => $withPrimary->id, 'email' => 'owner@primary.example', 'is_primary' => true]);
    ContactEloquentModel::factory()->create([...$team, 'customer_id' => $withNobody->id, 'email' => null]);
    $draft = InvoiceEloquentModel::factory()->create([...$team, 'customer_id' => $withBilling->id, 'status' => InvoiceStatusEnum::Draft->value]);
    $sent = InvoiceEloquentModel::factory()->create([...$team, 'customer_id' => $withPrimary->id, 'status' => InvoiceStatusEnum::Sent->value]);
    $skipped = InvoiceEloquentModel::factory()->create([...$team, 'customer_id' => $withNobody->id, 'status' => InvoiceStatusEnum::Draft->value]);

    actingAs($user)
        ->post(route('invoices.send-many'), ['invoice_ids' => [$draft->id, $sent->id, $skipped->id]])
        ->assertInertiaFlash('toast', ['type' => 'warning', 'message' => 'Sending 2 invoices. Not sent, as no contact with an email gets invoices: Quiet Co.']);

    $sentTo = [];
    Notification::assertSentOnDemand(InvoiceNotification::class, function (InvoiceNotification $notification, array $channels, AnonymousNotifiable $notifiable) use (&$sentTo): bool {
        $sentTo[$notification->document->invoice->getId()->toString()] = $notifiable->routes['mail'];

        return true;
    });

    expect($sentTo)->toEqual([$draft->id => ['accounts@billing.example'], $sent->id => ['owner@primary.example']])
        ->and($draft->fresh()->status)->toBe('sent')
        ->and($skipped->fresh())->status->toBe('draft')->emailed_at->toBeNull();
});

it('refuses voided invoices', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $void = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'number' => 9, 'status' => InvoiceStatusEnum::Void->value]);

    actingAs($user)
        ->post(route('invoices.send-many'), ['invoice_ids' => [$void->id]])
        ->assertSessionHasErrors(['invoice_ids' => 'INV-0009 is voided and can’t be sent.']);
});

it("returns not found for another team's invoice", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('invoices.send-many'), ['invoice_ids' => [InvoiceEloquentModel::factory()->create()->id]])
        ->assertNotFound();
});
