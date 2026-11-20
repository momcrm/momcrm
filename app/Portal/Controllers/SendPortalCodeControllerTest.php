<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\EmailCode\Notifications\EmailCodeNotification;
use App\Portal\PortalSession;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\post;

beforeEach(fn () => Notification::fake());

it('emails a portal code to a contact with portal access', function (): void {
    ContactEloquentModel::factory()->withPortalAccess()->create(['email' => 'Sam@Acme.test']);

    post(route('portal.login.code.send'), ['email' => 'sam@acme.test'])
        ->assertRedirectToRoute('portal.login.code')
        ->assertSessionHas(PortalSession::PENDING_EMAIL, 'sam@acme.test');

    Notification::assertSentOnDemand(EmailCodeNotification::class, fn (EmailCodeNotification $notification): bool => $notification->purpose->value === 'portal_sign_in');
});

it('looks the same but sends nothing to an address without portal access', function (): void {
    ContactEloquentModel::factory()->create(['email' => 'sam@acme.test']);

    post(route('portal.login.code.send'), ['email' => 'sam@acme.test'])
        ->assertRedirectToRoute('portal.login.code')
        ->assertSessionHas(PortalSession::PENDING_EMAIL, 'sam@acme.test');

    Notification::assertNothingSent();
});
