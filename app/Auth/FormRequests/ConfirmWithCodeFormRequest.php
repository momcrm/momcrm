<?php

declare(strict_types=1);

namespace App\Auth\FormRequests;

use App\Auth\AuthSession;
use App\EmailCode\Actions\VerifyEmailCode;
use App\EmailCode\EmailCodeCheckEnum;
use App\EmailCode\EmailCodePurposeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Confirms it's you with an emailed code instead of a password, for the same
 * protected pages Laravel's password confirmation guards.
 */
final class ConfirmWithCodeFormRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'digits:6'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => __('Enter the 6-digit code from the email.'),
            'code.digits' => __('Enter the 6-digit code from the email.'),
        ];
    }

    /**
     * @throws ValidationException
     */
    public function process(): RedirectResponse
    {
        $result = VerifyEmailCode::check((string) $this->user()?->email, EmailCodePurposeEnum::Confirm, $this->string('code')->toString());

        if ($result !== EmailCodeCheckEnum::Valid) {
            throw ValidationException::withMessages(['code' => $result->message()]);
        }

        $this->session()->put(AuthSession::PASSWORD_CONFIRMED_AT, time());

        return redirect()->intended(route('dashboard'));
    }
}
