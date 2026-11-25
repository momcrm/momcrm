<?php

declare(strict_types=1);

namespace App\Message;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Message>
 */
final class MessageCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Message::class;
    }
}
