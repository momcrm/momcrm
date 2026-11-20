<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Payment\Notifications\PaymentReceiptNotification;
use App\Payment\PaymentEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

it('emails the receipt, with its PDF, to the chosen contacts', function (): void {
    Notification::fake();
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $payment = PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'number' => 3]);
    $contact = fn (string $email) => ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $payment->customer_id, 'email' => $email]);
    $accounts = $contact('accounts@acme.example');
    $boss = $contact('boss@acme.example');
    $contact('not-chosen@acme.example');

    actingAs($user)
        ->post(route('payments.receipt.send', $payment->id), ['contact_ids' => [$accounts->id, $boss->id]])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Receipt PAY-0003 sent to accounts@acme.example, boss@acme.example.']);

    Notification::assertSentOnDemand(PaymentReceiptNotification::class, function (PaymentReceiptNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool {
        $mail = $notification->toMail($notifiable);

        return $notifiable->routes['mail'] === ['accounts@acme.example', 'boss@acme.example']
            && $mail->subject === 'Receipt PAY-0003 from '.$notification->document->team->getNameOnInvoices()
            && $mail->rawAttachments[0]['name'] === 'PAY-0003.pdf';
    });
});

it("refuses a contact who isn't the customer's or has no email", function (bool $isTheirs, ?string $email): void {
    Notification::fake();
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $payment = PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $contact = ContactEloquentModel::factory()->create([
        'team_id' => $user->current_team_id,
        'customer_id' => $isTheirs ? $payment->customer_id : CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id])->id,
        'email' => $email,
    ]);

    actingAs($user)
        ->post(route('payments.receipt.send', $payment->id), ['contact_ids' => [$contact->id]])
        ->assertSessionHasErrors(['contact_ids' => 'Choose contacts of this customer who have an email address.']);

    Notification::assertNothingSent();
})->with([
    'no email' => [true, null],
    "another customer's" => [false, 'someone@else.example'],
]);

it('asks who to send it to', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $payment = PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('payments.receipt.send', $payment->id), ['contact_ids' => []])
        ->assertSessionHasErrors(['contact_ids' => 'Choose who to send it to.']);
});

it("returns not found for another team's payment", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('payments.receipt.send', PaymentEloquentModel::factory()->create()->id), ['contact_ids' => []])
        ->assertNotFound();
});
