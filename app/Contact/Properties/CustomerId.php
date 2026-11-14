<?php

declare(strict_types=1);

namespace App\Contact\Properties;

use App\Shared\Abstracts\AbstractUuid;
use Illuminate\Foundation\Http\FormRequest;

final class CustomerId extends AbstractUuid
{
    public static function getName(): string
    {
        return 'customer_id';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', 'uuid'];
    }
}
