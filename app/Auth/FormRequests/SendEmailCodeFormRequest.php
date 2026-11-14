<?php

declare(strict_types=1);

namespace App\Auth\FormRequests;

use App\Auth\AuthSession;
use App\EmailCode\Actions\CreateEmailCode;
use App\EmailCode\EmailCodePurposeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

/**
 * Emails a sign-in code. The response is the same whether or not the address
 * has an account, so the form does not reveal who uses MomCRM.
 */
final class SendEmailCodeFormRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => __('Enter your email address.'),
            'email.email' => __('Enter a valid email address.'),
        ];
    }

    public function process(): RedirectResponse
    {
        $email = Str::lower($this->string('email')->trim()->toString());

        CreateEmailCode::send($email, EmailCodePurposeEnum::SignIn);

        $this->session()->put(AuthSession::PENDING_EMAIL, $email);

        return to_route('auth.code');
    }
}
