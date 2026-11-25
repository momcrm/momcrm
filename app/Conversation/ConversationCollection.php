<?php

declare(strict_types=1);

namespace App\Conversation;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Conversation>
 */
final class ConversationCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Conversation::class;
    }
}
