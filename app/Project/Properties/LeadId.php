<?php

declare(strict_types=1);

namespace App\Project\Properties;

use App\Shared\Abstracts\AbstractUuid;
use Illuminate\Foundation\Http\FormRequest;

final class LeadId extends AbstractUuid
{
    public static function getName(): string
    {
        return 'lead_id';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'uuid'];
    }
}
