<?php

declare(strict_types=1);

namespace App\Auth\FormRequests;

use App\Auth\Actions\CreateAccount;
use App\Auth\Actions\LogInUser;
use App\Auth\AuthSession;
use App\SocialAccount\Actions\LinkSocialAccount;
use App\User\Properties\Email;
use App\User\Properties\Name;
use App\User\UserEloquentModel;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rules\Password;

/**
 * "What should we call you?": creates the account for a proven email address.
 */
final class CreateAccountFormRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            Name::getNameForValidation() => Name::getValidationRulesForCreate($this),
            'password' => ['nullable', 'string', Password::default()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('Tell us what to call you.'),
        ];
    }

    public function process(): RedirectResponse
    {
        $email = $this->session()->get(AuthSession::VERIFIED_EMAIL);

        if (! is_string($email)) {
            return to_route('register');
        }

        $user = UserEloquentModel::query()->where(Email::getDatabaseTableColumnName(), $email)->first();

        if ($user === null) {
            $user = CreateAccount::for(
                email: $email,
                name: $this->string(Name::getNameForValidation())->trim()->toString(),
                password: $this->filled('password') ? $this->string('password')->toString() : null,
            );

            event(new Registered($user));
        }

        LinkSocialAccount::fromSession($this->session(), $user);
        $this->session()->forget([AuthSession::VERIFIED_EMAIL, AuthSession::SUGGESTED_NAME]);

        return LogInUser::andRedirect($this, $user);
    }
}
