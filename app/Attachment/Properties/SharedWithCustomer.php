<?php

declare(strict_types=1);

namespace App\Attachment\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractBoolean;

/**
 * Whether the customer can see and download a ticket's file in the customer
 * portal. Files the customer added there always can.
 */
final class SharedWithCustomer extends AbstractBoolean
{
    public static function getName(): string
    {
        return 'shared_with_customer';
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
