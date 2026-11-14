<?php

declare(strict_types=1);

namespace App\User\Actions;

use App\Team\Actions\CreatePersonalTeam;
use App\User\Properties\Email;
use App\User\Properties\Name;
use App\User\Properties\Password;
use App\User\User;
use App\User\UserEloquentModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Throwable;

/**
 * Fortify's registration hook. Fortify calls create() with the raw input.
 */
final readonly class RegisterUser implements CreatesNewUsers
{
    /**
     * @param  array<string, string>  $input
     *
     * @throws Throwable
     */
    public function create(array $input): UserEloquentModel
    {
        $request = FormRequest::createFrom(request());

        $validated = Validator::make($input, [
            Name::getNameForValidation() => Name::getValidationRulesForCreate($request),
            Email::getNameForValidation() => Email::getValidationRulesForCreate($request),
            Password::getNameForValidation() => Password::getValidationRulesForCreate($request),
        ])->validate();

        return DB::transaction(function () use ($validated): UserEloquentModel {
            $user = CreateUser::fromUser(User::fromValidation($validated));

            CreatePersonalTeam::fromUser($user);

            return UserEloquentModel::query()->findOrFail($user->getId()->toString());
        });
    }
}
