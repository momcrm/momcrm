<?php

declare(strict_types=1);

namespace App\Ticket\Properties;

use App\Shared\Abstracts\AbstractUuid;
use Illuminate\Foundation\Http\FormRequest;

/**
 * One of the team's ticket types (App\Choice, list `ticket_type`).
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
