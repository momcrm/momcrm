<?php

declare(strict_types=1);

namespace App\Folder;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Folder>
 */
final class FolderCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Folder::class;
    }
}
