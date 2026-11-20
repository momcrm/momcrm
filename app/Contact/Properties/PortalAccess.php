<?php

declare(strict_types=1);

namespace App\Contact\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractBoolean;

/**
 * Whether the contact may sign in to the customer portal. What they can see
 * and do there is up to the team's contact access rules.
 */
final class PortalAccess extends AbstractBoolean
{
    public static function getName(): string
    {
        return 'portal_access';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['boolean'];
    }

    public function default(): bool
    {
        return false;
    }
}
