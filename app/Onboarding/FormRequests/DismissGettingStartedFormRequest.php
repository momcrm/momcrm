<?php

declare(strict_types=1);

namespace App\Onboarding\FormRequests;

use App\User\Actions\UpdateUser;
use App\User\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;

final class DismissGettingStartedFormRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }

    public function process(): RedirectResponse
    {
        UpdateUser::fromUser(
            User::fromEloquentModel($this->user())->setGettingStartedDismissedAt(CarbonImmutable::now()),
        );

        return back();
    }
}
