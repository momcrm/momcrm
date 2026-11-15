<?php

declare(strict_types=1);

namespace App\Ticket\Properties;

use App\Shared\Abstracts\AbstractUuid;
use Illuminate\Foundation\Http\FormRequest;

/**
 * One of the team's ticket departments (App\Choice, list `ticket_department`).
 */
final class DepartmentId extends AbstractUuid
{
    public static function getName(): string
    {
        return 'department_id';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'uuid'];
    }
}
