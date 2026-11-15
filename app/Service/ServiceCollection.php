<?php

declare(strict_types=1);

namespace App\Service;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Service>
 */
final class ServiceCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Service::class;
    }
}
