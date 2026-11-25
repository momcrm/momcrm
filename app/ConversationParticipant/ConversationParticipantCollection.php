<?php

declare(strict_types=1);

namespace App\ConversationParticipant;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, ConversationParticipant>
 */
final class ConversationParticipantCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return ConversationParticipant::class;
    }
}
