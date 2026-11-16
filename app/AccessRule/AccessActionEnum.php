<?php

declare(strict_types=1);

namespace App\AccessRule;

use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;

/**
 * What someone is trying to do to a resource. Each resource lists the actions
 * it supports in AccessResourceEnum::actions().
 */
enum AccessActionEnum: string
{
    use Enum;
    use EnumOptions;

    case Archive = 'archive';
    case ChangeStatus = 'change_status';
    case ChangeVisibility = 'change_visibility';
    case Close = 'close';
    case Create = 'create';
    case Delete = 'delete';
    case Import = 'import';
    case ManageMembers = 'manage_members';
    case Move = 'move';
    case Reopen = 'reopen';
    case Reveal = 'reveal';
    case Restore = 'restore';
    case Update = 'update';

    public function label(): string
    {
        return match ($this) {
            self::Archive => 'Archive',
            self::ChangeStatus => 'Change status',
            self::ChangeVisibility => 'Change visibility',
            self::Close => 'Close',
            self::Create => 'Create',
            self::Delete => 'Delete',
            self::Import => 'Import',
            self::ManageMembers => 'Manage members',
            self::Move => 'Move',
            self::Reopen => 'Reopen',
            self::Reveal => 'Reveal',
            self::Restore => 'Restore',
            self::Update => 'Edit',
        };
    }
}
