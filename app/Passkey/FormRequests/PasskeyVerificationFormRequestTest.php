<?php

declare(strict_types=1);

use App\Passkey\FormRequests\PasskeyVerificationFormRequest;
use Illuminate\Validation\ValidationException;
use Laravel\Passkeys\Http\Requests\PasskeyVerificationRequest;

it('remembers people who log in with a passkey without them asking', function (): void {
    $resolved = null;
    app()->resolving(PasskeyVerificationRequest::class, function (PasskeyVerificationRequest $request) use (&$resolved): void {
        $resolved = $request;
    });

    expect(fn (): PasskeyVerificationRequest => app(PasskeyVerificationRequest::class))->toThrow(ValidationException::class)
        ->and($resolved)->toBeInstanceOf(PasskeyVerificationFormRequest::class)
        ->and($resolved?->has('remember'))->toBeFalse()
        ->and($resolved?->remember())->toBeTrue();
});
