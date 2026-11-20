<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Customer\Notifications\CustomerStatementNotification;
use App\User\UserEloquentModel;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

it('emails the statement for the period, with its PDF, to the chosen contacts', function (): void {
    Notification::fake();
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Acme IT']);
    $contact = ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'email' => 'accounts@acme.example']);

    actingAs($user)
        ->post(route('customers.statement.send', $customer->id), ['from' => '2026-10-01', 'to' => '2026-10-31', 'contact_ids' => [$contact->id]])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Statement sent to accounts@acme.example.']);

    Notification::assertSentOnDemand(CustomerStatementNotification::class, function (CustomerStatementNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool {
        $mail = $notification->toMail($notifiable);

        return $notifiable->routes['mail'] === ['accounts@acme.example']
            && $notification->statement->from->toDateString() === '2026-10-01'
            && $notification->statement->to->toDateString() === '2026-10-31'
            && $mail->rawAttachments[0]['name'] === 'Statement Acme IT 2026-10-31.pdf';
    });
});

it('asks for the period and who to send it to', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('customers.statement.send', $customer->id), [])
        ->assertSessionHasErrors(['from', 'to', 'contact_ids' => 'Choose who to send it to.']);
});

it("returns not found for another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('customers.statement.send', CustomerEloquentModel::factory()->create()->id), [])
        ->assertNotFound();
});
