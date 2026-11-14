<?php

declare(strict_types=1);

namespace App\User\FormRequests;

use App\EmailCode\Actions\VerifyEmailCode;
use App\EmailCode\EmailCodeCheckEnum;
use App\EmailCode\EmailCodePurposeEnum;
use App\User\Actions\DeleteUser;
use App\User\Properties\CurrentPassword;
use App\User\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

final class DeleteProfileFormRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        if ($this->user()?->hasPassword() === false) {
            // Without a password, an emailed code confirms it's really them.
            return ['code' => ['required', 'digits:6']];
        }

        return [
            // The form posts the current password as "password".
            'password' => CurrentPassword::getValidationRulesForCreate($this),
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
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty() || $this->user()?->hasPassword() !== false) {
                    return;
                }

                $result = VerifyEmailCode::check((string) $this->user()->email, EmailCodePurposeEnum::Confirm, $this->string('code')->toString());

                if ($result !== EmailCodeCheckEnum::Valid) {
                    $validator->errors()->add('code', $result->message());
                }
            },
        ];
    }

    public function process(): RedirectResponse
    {
        $user = User::fromEloquentModel($this->user());

        Auth::logout();

        DeleteUser::fromUser($user);

        $this->session()->invalidate();
        $this->session()->regenerateToken();

        return redirect('/');
    }
}
