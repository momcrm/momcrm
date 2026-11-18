<?php

declare(strict_types=1);

namespace App\Environment\Exceptions;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class CouldNotChangeEnvironmentVariable extends AccessDeniedHttpException
{
    public static function locked(string $key): self
    {
        return new self("{$key} cannot be changed or read here. Changing it breaks every encrypted value and signed-in session.");
    }
}
