<?php

declare(strict_types=1);

namespace App\Passkey\FormRequests;

use Laravel\Passkeys\Http\Requests\PasskeyVerificationRequest;

/**
 * Keeps people signed in after a passkey log-in, as every other way of logging
 * in does. The passkeys JavaScript client has no way to send `remember`.
 */
final class PasskeyVerificationFormRequest extends PasskeyVerificationRequest
{
    public function remember(): bool
    {
        return true;
    }
}
