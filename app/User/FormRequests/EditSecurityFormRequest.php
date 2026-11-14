<?php

declare(strict_types=1);

namespace App\User\FormRequests;

use Illuminate\Foundation\Http\FormRequest;
use Laravel\Fortify\InteractsWithTwoFactorState;

final class EditSecurityFormRequest extends FormRequest
{
    use InteractsWithTwoFactorState;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
