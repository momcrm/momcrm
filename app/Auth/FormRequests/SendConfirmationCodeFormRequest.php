<?php

declare(strict_types=1);

namespace App\Auth\FormRequests;

use App\EmailCode\Actions\CreateEmailCode;
use App\EmailCode\EmailCodePurposeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Emails a code so someone without a password can confirm it's them.
 */
final class SendConfirmationCodeFormRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }

    public function process(): RedirectResponse
    {
        CreateEmailCode::send((string) $this->user()?->email, EmailCodePurposeEnum::Confirm);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('We emailed you a code.')]);

        return back();
    }
}
