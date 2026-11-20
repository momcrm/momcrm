<?php

declare(strict_types=1);

namespace App\Payment\Properties;

use App\Shared\Abstracts\AbstractDateOnly;
use Illuminate\Foundation\Http\FormRequest;

final class PaidOn extends AbstractDateOnly
{
    public static function getName(): string
    {
        return 'paid_on';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', 'date'];
    }
}
