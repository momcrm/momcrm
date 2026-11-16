<?php

declare(strict_types=1);

namespace App\Customer;

use App\Shared\Traits\Enum;

enum CustomerRelationship: string
{
    use Enum;

    case Contacts = 'contacts';
    case Services = 'services';
    case Team = 'team';
}
