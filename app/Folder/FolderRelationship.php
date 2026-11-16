<?php

declare(strict_types=1);

namespace App\Folder;

use App\Shared\Traits\Enum;

enum FolderRelationship: string
{
    use Enum;

    case Customer = 'customer';
    case Parent = 'parent';
}
