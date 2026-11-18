<?php

declare(strict_types=1);

namespace App\Environment\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CouldNotFindEnvironmentVariable extends NotFoundHttpException
{
    public static function notFound(string $key): self
    {
        return new self("{$key} is not set in the environment file.");
    }
}
