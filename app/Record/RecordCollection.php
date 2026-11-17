<?php

declare(strict_types=1);

namespace App\Record;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Record>
 */
final class RecordCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Record::class;
    }
}
