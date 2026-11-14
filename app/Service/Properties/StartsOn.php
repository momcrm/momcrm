<?php

declare(strict_types=1);

namespace App\Service\Properties;

use App\Shared\Abstracts\AbstractDateOnly;
use Illuminate\Foundation\Http\FormRequest;

final class StartsOn extends AbstractDateOnly
{
    public static function getName(): string
    {
        return 'starts_on';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'date'];
    }
}
