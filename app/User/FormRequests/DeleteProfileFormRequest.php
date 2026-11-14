<?php

declare(strict_types=1);

namespace App\User\FormRequests;

use App\User\Actions\DeleteUser;
use App\User\Properties\CurrentPassword;
use App\User\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

final class DeleteProfileFormRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // The form posts the current password as "password".
            'password' => CurrentPassword::getValidationRulesForCreate($this),
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
