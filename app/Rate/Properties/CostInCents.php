<?php

declare(strict_types=1);

namespace App\Rate\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractInteger;

/**
 * What one unit costs the team for one month, when known, to see the margin.
 */
final class CostInCents extends AbstractInteger
{
    public static function getName(): string
    {
        return 'cost_in_cents';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'integer', 'min:0', 'max:100000000'];
    }
}
