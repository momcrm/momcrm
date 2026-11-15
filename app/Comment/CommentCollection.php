<?php

declare(strict_types=1);

namespace App\Comment;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Comment>
 */
final class CommentCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Comment::class;
    }
}
