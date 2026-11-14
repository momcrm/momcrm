<?php

declare(strict_types=1);

namespace App\Auth\FormRequests;

use App\Auth\Actions\LogInUser;
use App\Auth\AuthSession;
use App\EmailCode\Actions\VerifyEmailCode;
use App\EmailCode\EmailCodeCheckEnum;
use App\EmailCode\EmailCodePurposeEnum;
use App\SocialAccount\Actions\LinkSocialAccount;
use App\User\Properties\Email;
use App\User\UserEloquentModel;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Checks the emailed code. An existing account is logged in; a new address
 * moves on to "What should we call you?".
 */
final class VerifyEmailCodeFormRequest extends FormRequest
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
        $email = $this->session()->get(AuthSession::PENDING_EMAIL);

        if (! is_string($email)) {
            return to_route('signup');
        }

        $result = VerifyEmailCode::check($email, EmailCodePurposeEnum::SignIn, $this->string('code')->toString());

        if ($result !== EmailCodeCheckEnum::Valid) {
            throw ValidationException::withMessages(['code' => $result->message()]);
        }

        $this->session()->forget(AuthSession::PENDING_EMAIL);

        $user = UserEloquentModel::query()->where(Email::getDatabaseTableColumnName(), $email)->first();

        if ($user === null) {
            $this->session()->put(AuthSession::VERIFIED_EMAIL, $email);

            return to_route('signup.profile');
        }

        if ($user->email_verified_at === null) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        LinkSocialAccount::fromSession($this->session(), $user);

        return LogInUser::andRedirect($this, $user);
    }
}
