<?php

declare(strict_types=1);

namespace App\Service\Properties;

use App\Shared\Abstracts\AbstractUuid;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The rate the service is priced from.
 */
final class RateId extends AbstractUuid
{
    public static function getName(): string
    {
        return 'rate_id';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['required', 'uuid'];
    }
}
