<?php

declare(strict_types=1);

use App\Auth\AuthSession;
use App\EmailCode\Actions\VerifyEmailCode;
use App\EmailCode\EmailCodeEloquentModel;
use App\EmailCode\Notifications\EmailCodeNotification;
use App\User\UserEloquentModel;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\post;

beforeEach(function (): void {
    Notification::fake();
});

it('emails a 6-digit code and asks for it next', function (string $email): void {
    UserEloquentModel::factory()->create(['email' => 'existing@acme.test']);

    post(route('auth.code.send'), ['email' => $email])
        ->assertRedirectToRoute('auth.code')
        ->assertSessionHas(AuthSession::PENDING_EMAIL, mb_strtolower($email));

    $sentCode = null;
    Notification::assertSentOnDemand(
        EmailCodeNotification::class,
        function (EmailCodeNotification $notification, array $channels, AnonymousNotifiable $notifiable) use ($email, &$sentCode): bool {
            $sentCode = $notification->code;

            return $notifiable->routes['mail'] === mb_strtolower($email)
                && preg_match('/^\d{6}$/', $notification->code) === 1
                && $notification->toMail($notifiable)->subject === "Your MomCRM code is {$notification->code}";
        },
    );
    expect(EmailCodeEloquentModel::query()->sole()->code_hash)
        ->toBe(VerifyEmailCode::hash((string) $sentCode))
        ->not->toBe($sentCode);
})->with([
    'a new address' => 'New@Acme.test',
    'an existing account, answered the same way' => 'existing@acme.test',
]);

it('needs a valid email address', function (): void {
    post(route('auth.code.send'), ['email' => 'not-an-email'])
        ->assertSessionHasErrors(['email' => 'Enter a valid email address.']);

    Notification::assertNothingSent();
});

it('limits how often codes are sent to one address', function (): void {
    foreach (range(1, 3) as $attempt) {
        post(route('auth.code.send'), ['email' => 'sam@acme.test'])->assertRedirectToRoute('auth.code');
    }

    post(route('auth.code.send'), ['email' => 'sam@acme.test'])->assertTooManyRequests();
});
