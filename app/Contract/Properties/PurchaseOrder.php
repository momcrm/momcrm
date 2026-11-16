<?php

declare(strict_types=1);

namespace App\Contract\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractString;

final class PurchaseOrder extends AbstractString
{
    public static function getName(): string
    {
        return 'purchase_order';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'string', 'max:255'];
    }
}
