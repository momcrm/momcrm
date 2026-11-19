<?php

declare(strict_types=1);

namespace App\Service\Properties;

use App\Shared\Abstracts\AbstractUuid;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The customer's contact for this service, if there is one.
 */
final class ContactId extends AbstractUuid
{
    public static function getName(): string
    {
        return 'contact_id';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'uuid'];
    }
}
