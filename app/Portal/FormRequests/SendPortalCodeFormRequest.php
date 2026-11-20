<?php

declare(strict_types=1);

namespace App\Portal\FormRequests;

use App\EmailCode\Actions\CreateEmailCode;
use App\EmailCode\EmailCodePurposeEnum;
use App\Portal\Actions\FindPortalContacts;
use App\Portal\PortalSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

/**
 * Emails a portal sign-in code, but only to an address that has portal
 * access. The next page looks the same either way, so the form does not
 * reveal who is a customer.
 */
final class SendPortalCodeFormRequest extends FormRequest
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

        if (FindPortalContacts::existForEmail($email)) {
            CreateEmailCode::send($email, EmailCodePurposeEnum::PortalSignIn);
        }

        $this->session()->put(PortalSession::PENDING_EMAIL, $email);

        return to_route('portal.login.code');
    }
}
