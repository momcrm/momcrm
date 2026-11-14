<?php

declare(strict_types=1);

namespace App\User\FormRequests;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\User\Actions\UpdateUser;
use App\User\Properties\Email;
use App\User\Properties\Name;
use App\User\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;

final class UpdateProfileFormRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            Name::getNameForValidation() => Name::getValidationRulesForUpdate($this),
            Email::getNameForValidation() => Email::getValidationRulesForUpdate($this),
        ];
    }

    public function process(): RedirectResponse
    {
        $user = User::fromEloquentModel($this->user());
        $previousEmail = $user->getEmail()->toString();
        $previousName = $user->getName()->toString();

        $user->setPropertyIfExists($this->validated());

        if ($user->getEmail()->toString() !== $previousEmail) {
            $user->setEmailVerifiedAt(null);
        }

        UpdateUser::fromUser($user);

        $changedFields = array_keys(array_filter([
            Name::getName() => $user->getName()->toString() !== $previousName,
            Email::getName() => $user->getEmail()->toString() !== $previousEmail,
        ]));

        if ($changedFields !== []) {
            RecordActivity::for(
                type: ActivityTypeEnum::ProfileUpdated,
                userId: $user->getId()->toString(),
                details: ['fields' => $changedFields],
            );
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile saved.')]);

        return to_route('profile.edit');
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input(Email::getNameForValidation());

        if (config('fortify.lowercase_usernames') && is_string($email)) {
            $this->merge([Email::getNameForValidation() => Str::lower($email)]);
        }
    }
}
