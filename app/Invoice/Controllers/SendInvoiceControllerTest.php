<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceStatusEnum;
use App\Invoice\Notifications\InvoiceNotification;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\Payment\PaymentEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

it('emails a draft invoice with its PDF, marking it sent and applying credit', function (): void {
    Notification::fake();
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'number' => 42, 'status' => InvoiceStatusEnum::Draft->value, 'tax_rate_basis_points' => 0]);
    InvoiceItemEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'invoice_id' => $invoice->id, 'quantity' => 1, 'unit_price_in_cents' => 10000]);
    PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $invoice->customer_id, 'amount_in_cents' => 2500]);
    $accounts = ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $invoice->customer_id, 'email' => 'accounts@acme.example']);

    actingAs($user)
        ->post(route('invoices.send', $invoice->id), ['contact_ids' => [$accounts->id]])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'INV-0042 sent to accounts@acme.example.']);

    expect($invoice->fresh())
        ->status->toBe('sent')
        ->emailed_at->not->toBeNull();

    Notification::assertSentOnDemand(InvoiceNotification::class, function (InvoiceNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool {
        $mail = $notification->toMail($notifiable);

        return $notifiable->routes['mail'] === ['accounts@acme.example']
            && $mail->subject === 'Invoice INV-0042 from '.$notification->document->team->getNameOnInvoices()
            && in_array('Invoice INV-0042 for A$75.00 is due on '.$notification->document->invoice->getDueOn()->toCarbon()->format('j M Y').'.', $mail->introLines, true)
            && $mail->rawAttachments[0]['name'] === 'INV-0042.pdf';
    });
});

it('refuses a voided invoice and contacts of another customer', function (string $status, bool $isTheirs): void {
    Notification::fake();
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'status' => $status]);
    $contact = ContactEloquentModel::factory()->create([
        'team_id' => $user->current_team_id,
        'customer_id' => $isTheirs ? $invoice->customer_id : ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id])->customer_id,
        'email' => 'someone@example.com',
    ]);

    actingAs($user)
        ->post(route('invoices.send', $invoice->id), ['contact_ids' => [$contact->id]])
        ->assertSessionHasErrors('contact_ids');

    Notification::assertNothingSent();
})->with([
    'voided' => ['void', true],
    'another customer' => ['sent', false],
]);
