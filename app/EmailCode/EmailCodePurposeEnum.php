<?php

declare(strict_types=1);

namespace App\EmailCode;

use App\Shared\Traits\Enum;

enum EmailCodePurposeEnum: string
{
    use Enum;

    case Confirm = 'confirm';
    case SignIn = 'sign_in';

    public function label(): string
    {
        return match ($this) {
            self::Confirm => 'Confirm it is you',
            self::SignIn => 'Sign in or sign up',
        };
    }
}
