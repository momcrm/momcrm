<?php

declare(strict_types=1);

namespace App\Environment\Exceptions;

use RuntimeException;

/**
 * The web server cannot write the `.env`. The message is shown to the
 * platform admin who tried to change it.
 */
final class CouldNotWriteEnvironmentFile extends RuntimeException
{
    public static function notWritable(): self
    {
        return new self('The environment file is not writable by the web server.');
    }
}
