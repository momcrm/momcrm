<?php

declare(strict_types=1);

namespace App\Conversation;

use App\Conversation\Factories\ConversationFactory;
use App\Conversation\Properties\Id;
use App\Conversation\Properties\LastMessageAt;
use App\ConversationParticipant\ConversationParticipantEloquentModel;
use App\ConversationParticipant\Properties\ConversationId;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A private conversation between members of a team. Personal, so not
 * audited.
 *
 * @property CarbonImmutable|null $created_at
 * @property string $id
 * @property CarbonImmutable|null $last_message_at
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 */
#[ScopedBy(CurrentTeamScope::class)]
final class ConversationEloquentModel extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function getTable(): string
    {
        return Table::Conversations->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    /**
     * @return HasMany<ConversationParticipantEloquentModel, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipantEloquentModel::class, ConversationId::getDatabaseTableColumnName());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            LastMessageAt::getDatabaseTableColumnName() => 'datetime',
        ];
    }

    protected static function newFactory(): ConversationFactory
    {
        return ConversationFactory::new();
    }
}
