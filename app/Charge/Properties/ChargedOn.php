<?php

declare(strict_types=1);

namespace App\Charge\Properties;

use App\Shared\Abstracts\AbstractDateOnly;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The day the thing was supplied or done.
 */
final class ChargedOn extends AbstractDateOnly
{
    public static function getName(): string
    {
        return 'charged_on';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', 'date'];
    }
}
