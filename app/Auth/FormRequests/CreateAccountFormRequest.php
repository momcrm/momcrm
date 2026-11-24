<?php

declare(strict_types=1);

namespace App\Auth\FormRequests;

use App\Auth\Actions\CreateAccount;
use App\Auth\Actions\LogInUser;
use App\Auth\AuthSession;
use App\SocialAccount\Actions\LinkSocialAccount;
use App\User\Properties\Email;
use App\User\Properties\FirstName;
use App\User\Properties\LastName;
use App\User\UserEloquentModel;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rules\Password;

/**
 * "What's your name?": creates the account for a proven email address. Like a
 * Microsoft account, it needs both a first and a last name.
 */
final class CreateAccountFormRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            FirstName::getNameForValidation() => FirstName::getValidationRulesForCreate($this),
            LastName::getNameForValidation() => LastName::getValidationRulesForCreate($this),
            'password' => ['nullable', 'string', Password::default()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => __('Enter your first name.'),
            'last_name.required' => __('Enter your last name.'),
        ];
    }

    public function process(): RedirectResponse
    {
        $email = $this->session()->get(AuthSession::VERIFIED_EMAIL);

        if (! is_string($email)) {
            return to_route('signup');
        }

        $user = UserEloquentModel::query()->where(Email::getDatabaseTableColumnName(), $email)->first();

        if ($user === null) {
            $user = CreateAccount::for(
                email: $email,
                firstName: $this->string(FirstName::getNameForValidation())->trim()->toString(),
                lastName: $this->string(LastName::getNameForValidation())->trim()->toString(),
                password: $this->filled('password') ? $this->string('password')->toString() : null,
            );

            event(new Registered($user));
        }

        LinkSocialAccount::fromSession($this->session(), $user);
        $this->session()->forget([AuthSession::VERIFIED_EMAIL, AuthSession::SUGGESTED_NAME]);

        return LogInUser::andRedirect($this, $user);
    }
}
