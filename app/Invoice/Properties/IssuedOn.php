<?php

declare(strict_types=1);

namespace App\Invoice\Properties;

use App\Shared\Abstracts\AbstractDateOnly;
use Illuminate\Foundation\Http\FormRequest;

final class IssuedOn extends AbstractDateOnly
{
    public static function getName(): string
    {
        return 'issued_on';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', 'date'];
    }
}
