<?php

declare(strict_types=1);

namespace App\EmailCode;

use App\Shared\Traits\Enum;

/**
 * The outcome of checking a code someone typed in.
 */
enum EmailCodeCheckEnum: string
{
    use Enum;

    case Expired = 'expired';
    case Invalid = 'invalid';
    case TooManyAttempts = 'too_many_attempts';
    case Valid = 'valid';

    public function message(): string
    {
        return match ($this) {
            self::Expired => __('That code has expired. Send a new one.'),
            self::Invalid => __("That code isn't right. Check the email and try again."),
            self::TooManyAttempts => __('Too many wrong tries. Send a new code.'),
            self::Valid => '',
        };
    }
}
