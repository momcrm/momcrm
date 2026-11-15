<?php

declare(strict_types=1);

namespace App\Attachment;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Attachment>
 */
final class AttachmentCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Attachment::class;
    }
}
