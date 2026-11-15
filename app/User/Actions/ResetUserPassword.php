<?php

declare(strict_types=1);

namespace App\User\Actions;

use App\Shared\Types;
use App\User\Properties\Password;
use App\User\User;
use App\User\UserEloquentModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

/**
 * Fortify's password reset hook. Fortify calls reset() with the raw input.
 */
final readonly class ResetUserPassword implements ResetsUserPasswords
{
    /**
     * @param  array<string, string>  $input
     */
    public function reset(UserEloquentModel $user, array $input): void
    {
        $validated = Validator::make($input, [
            Password::getNameForValidation() => Password::getValidationRulesForCreate(FormRequest::createFrom(request())),
        ])->validate();

        UpdateUserPassword::fromUser(
            User::fromEloquentModel($user)->setPassword(Types::string($validated[Password::getNameForValidation()])),
        );
    }
}
