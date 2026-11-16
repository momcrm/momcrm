<?php

declare(strict_types=1);

namespace App\Folder;

use App\Folder\Properties\ParentId;
use App\Shared\Table;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Rules for pointing at one of a customer's folders, as a folder's parent
 * or as where a file goes.
 */
final class FolderValidationRules
{
    public static function inCustomer(?string $customerId): Exists
    {
        return Rule::exists(Table::Folders->value, 'id')->where('customer_id', $customerId);
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            ParentId::getNameForValidation().'.exists' => __('Choose one of this customer\'s folders.'),
            'folder_id.exists' => __('Choose one of this customer\'s folders.'),
        ];
    }
}
