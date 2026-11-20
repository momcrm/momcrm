<?php

declare(strict_types=1);

namespace App\Contact\Properties;

use App\Shared\Abstracts\AbstractUuid;
use Illuminate\Foundation\Http\FormRequest;

/**
 * One of the team's contact roles (App\Choice, list `contact_role`), which
 * portal access rules can look at.
 */
final class ContactRoleId extends AbstractUuid
{
    public static function getName(): string
    {
        return 'contact_role_id';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'uuid'];
    }
}
