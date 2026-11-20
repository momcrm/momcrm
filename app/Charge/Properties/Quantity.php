<?php

declare(strict_types=1);

namespace App\Charge\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractInteger;

final class Quantity extends AbstractInteger
{
    public static function getName(): string
    {
        return 'quantity';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', 'integer', 'min:1', 'max:100000'];
    }
}
