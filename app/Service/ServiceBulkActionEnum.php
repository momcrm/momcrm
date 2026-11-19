<?php

declare(strict_types=1);

namespace App\Service;

use App\Shared\Traits\Enum;

/**
 * What the services list can do to several services at once, beyond
 * changing their fields.
 */
enum ServiceBulkActionEnum: string
{
    use Enum;

    case Activate = 'activate';
    case Deactivate = 'deactivate';
    case Archive = 'archive';
    case Restore = 'restore';

    /**
     * The policy ability each service must allow.
     */
    public function ability(): string
    {
        return match ($this) {
            self::Activate, self::Deactivate => 'update',
            self::Archive => 'archive',
            self::Restore => 'restore',
        };
    }

    /**
     * The toast once it is done, for one or more services.
     */
    public function message(int $count): string
    {
        return match ($this) {
            self::Activate => trans_choice('{1} 1 service turned on.|[2,*] :count services turned on.', $count),
            self::Deactivate => trans_choice('{1} 1 service turned off.|[2,*] :count services turned off.', $count),
            self::Archive => trans_choice('{1} 1 service archived.|[2,*] :count services archived.', $count),
            self::Restore => trans_choice('{1} 1 service restored.|[2,*] :count services restored.', $count),
        };
    }
}
