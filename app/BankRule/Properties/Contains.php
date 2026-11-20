<?php

declare(strict_types=1);

namespace App\BankRule\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

/**
 * The text a deposit's description must contain for the rule to apply,
 * compared without regard to case or spacing.
 */
final class Contains extends AbstractString
{
    public static function getName(): string
    {
        return 'contains';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', 'string', 'min:3', 'max:100'];
    }
}
