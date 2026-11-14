<?php

declare(strict_types=1);

namespace App\Ticket\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractInteger;

final class EstimateMinutes extends AbstractInteger
{
    public static function getName(): string
    {
        return 'estimate_minutes';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'integer', 'min:0', 'max:100000'];
    }
}
