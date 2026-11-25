<?php

declare(strict_types=1);

namespace App\ConversationParticipant;

use App\ConversationParticipant\Factories\ConversationParticipantFactory;
use App\ConversationParticipant\Properties\Id;
use App\ConversationParticipant\Properties\LastReadAt;
use App\Shared\Table;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Someone in a conversation, and how far they have read it. Reached through
 * the conversation, which belongs to the team.
 *
 * @property string $conversation_id
 * @property CarbonImmutable|null $created_at
 * @property string $id
 * @property CarbonImmutable|null $last_read_at
 * @property CarbonImmutable|null $updated_at
 * @property string $user_id
 */
final class ConversationParticipantEloquentModel extends Model
{
    /** @use HasFactory<ConversationParticipantFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function getTable(): string
    {
        return Table::ConversationParticipants->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            LastReadAt::getDatabaseTableColumnName() => 'datetime',
        ];
    }

    protected static function newFactory(): ConversationParticipantFactory
    {
        return ConversationParticipantFactory::new();
    }
}
