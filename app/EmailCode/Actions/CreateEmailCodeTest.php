<?php

declare(strict_types=1);

use App\EmailCode\Actions\CreateEmailCode;
use App\EmailCode\Actions\VerifyEmailCode;
use App\EmailCode\EmailCodeCheckEnum;
use App\EmailCode\EmailCodePurposeEnum;
use App\EmailCode\Notifications\EmailCodeNotification;
use Illuminate\Support\Facades\Notification;

it('makes only the latest emailed code work', function (): void {
    Notification::fake();
    $codes = [];

    CreateEmailCode::send('sam@acme.test', EmailCodePurposeEnum::SignIn);
    CreateEmailCode::send('sam@acme.test', EmailCodePurposeEnum::SignIn);
    Notification::assertSentOnDemand(EmailCodeNotification::class, function (EmailCodeNotification $notification) use (&$codes): bool {
        $codes[] = $notification->code;

        return true;
    });

    [$first, $latest] = $codes;
    if ($first !== $latest) {
        expect(VerifyEmailCode::check('sam@acme.test', EmailCodePurposeEnum::SignIn, $first))->toBe(EmailCodeCheckEnum::Invalid);
    }
    expect(VerifyEmailCode::check('sam@acme.test', EmailCodePurposeEnum::SignIn, $latest))->toBe(EmailCodeCheckEnum::Valid);
});
