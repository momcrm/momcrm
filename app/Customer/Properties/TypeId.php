<?php

declare(strict_types=1);

namespace App\Customer\Properties;

use App\Shared\Abstracts\AbstractUuid;
use Illuminate\Foundation\Http\FormRequest;

/**
 * One of the team's customer types (App\Choice, list `customer_type`).
 */
final class TypeId extends AbstractUuid
{
    public static function getName(): string
    {
        return 'type_id';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'uuid'];
    }
}
