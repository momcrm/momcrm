<?php

declare(strict_types=1);

namespace App\Contact;

use App\Shared\Traits\Enum;

enum ContactRelationship: string
{
    use Enum;

    case Customer = 'customer';
}
