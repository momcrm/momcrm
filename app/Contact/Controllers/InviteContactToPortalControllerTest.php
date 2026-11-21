<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Contact\Notifications\PortalInvitationNotification;
use App\Customer\CustomerEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

beforeEach(fn () => Notification::fake());

it('lets the contact into the portal and emails them a link to sign in', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $contact = ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'email' => 'sam@acme.test']);

    actingAs($user)
        ->post(route('contacts.portal-invitation', $contact->id))
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($contact->fresh())
        ->portal_access->toBeTrue()
        ->portal_invited_at->not->toBeNull();

    Notification::assertSentOnDemand(
        PortalInvitationNotification::class,
        fn (PortalInvitationNotification $notification, array $channels, object $notifiable): bool => $notifiable->routes['mail'] === 'sam@acme.test'
            && str_contains($notification->toMail($notifiable)->actionUrl, '/login?email=sam%40acme.test'),
    );
});

it('needs an email address to invite', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $contact = ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'email' => null]);

    actingAs($user)->post(route('contacts.portal-invitation', $contact->id))->assertSessionHasErrors('email');

    expect($contact->fresh()->portal_access)->toBeFalse();
    Notification::assertNothingSent();
});

it("can't invite another team's contact", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($user)->post(route('contacts.portal-invitation', ContactEloquentModel::factory()->create()->id))->assertNotFound();
});
