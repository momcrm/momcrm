<?php

declare(strict_types=1);

namespace App\Attachment\Properties;

use App\Shared\Abstracts\AbstractUuid;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The folder a customer's file sits in (App\Folder), or none for the top level.
 */
final class FolderId extends AbstractUuid
{
    public static function getName(): string
    {
        return 'folder_id';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'uuid'];
    }
}
