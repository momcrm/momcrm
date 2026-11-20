<?php

declare(strict_types=1);

namespace App\BankTransaction\Statements;

use RuntimeException;

/**
 * A statement file that isn't one we can read, said in a way the person who
 * uploaded it can act on.
 */
final class CouldNotReadStatement extends RuntimeException
{
    public static function because(string $fileName, string $reason): self
    {
        return new self(__(':file: :reason', ['file' => $fileName, 'reason' => $reason]));
    }
}
