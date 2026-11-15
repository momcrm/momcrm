<?php

declare(strict_types=1);

namespace App\Ticket\Properties;

use App\Shared\Abstracts\AbstractUuid;
use Illuminate\Foundation\Http\FormRequest;

/**
 * One of the team's ticket statuses (App\Choice, list `ticket_status`). Its
 * category says whether the ticket is open or closed.
 */
final class StatusId extends AbstractUuid
{
    public static function getName(): string
    {
        return 'status_id';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'uuid'];
    }
}
