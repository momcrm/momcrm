<?php

declare(strict_types=1);

namespace App\Secret\Properties;

use App\Shared\Abstracts\AbstractUuid;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The customer's asset the secret is for, such as a firewall's admin login,
 * or none for the customer as a whole.
 */
final class AssetId extends AbstractUuid
{
    public static function getName(): string
    {
        return 'asset_id';
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRulesForCreate(FormRequest $request): array
    {
        return ['nullable', 'uuid'];
    }
}
