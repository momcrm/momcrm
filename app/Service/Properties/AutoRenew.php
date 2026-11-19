<?php

declare(strict_types=1);

namespace App\Service\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractBoolean;

/**
 * Whether the service renews by itself when it expires, such as a domain name.
 */
final class AutoRenew extends AbstractBoolean
{
    public static function getName(): string
    {
        return 'auto_renew';
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
