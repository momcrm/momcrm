<?php

declare(strict_types=1);

namespace App\Portal\FormRequests;

use App\EmailCode\Actions\VerifyEmailCode;
use App\EmailCode\EmailCodeCheckEnum;
use App\EmailCode\EmailCodePurposeEnum;
use App\Portal\Actions\LogInContact;
use App\Portal\PortalSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Checks the emailed code and signs the contact in to the portal.
 */
final class VerifyPortalCodeFormRequest extends FormRequest
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
        $email = $this->session()->get(PortalSession::PENDING_EMAIL);

        if (! is_string($email)) {
            return to_route('portal.login');
        }

        $result = VerifyEmailCode::check($email, EmailCodePurposeEnum::PortalSignIn, $this->string('code')->toString());

        if ($result !== EmailCodeCheckEnum::Valid) {
            throw ValidationException::withMessages(['code' => $result->message()]);
        }

        return LogInContact::forProvenEmail($this, $email);
    }
}
