<?php

declare(strict_types=1);

namespace App\Connector\Sdk;

use RuntimeException;

/**
 * What was typed into a connector's form did not work. It names the field to
 * look at, and the form shows the reason beneath it.
 */
final class CouldNotConnect extends RuntimeException
{
    private function __construct(
        public readonly string $field,
        string $reason,
    ) {
        parent::__construct($reason);
    }

    public static function because(string $field, string $reason): self
    {
        return new self($field, $reason);
    }
}
