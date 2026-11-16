<?php

declare(strict_types=1);

namespace App\Folder\Properties;

use App\Shared\Abstracts\AbstractUuid;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The folder this one sits in, or none for the customer's top level.
 */
final class ParentId extends AbstractUuid
{
    public static function getName(): string
    {
        return 'parent_id';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'uuid'];
    }
}
