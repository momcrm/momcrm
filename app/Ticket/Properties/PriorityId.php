<?php

declare(strict_types=1);

namespace App\Ticket\Properties;

use App\Shared\Abstracts\AbstractUuid;
use Illuminate\Foundation\Http\FormRequest;

/**
 * One of the team's ticket priorities (App\Choice, list `ticket_priority`).
 */
final class PriorityId extends AbstractUuid
{
    public static function getName(): string
    {
        return 'priority_id';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'uuid'];
    }
}
