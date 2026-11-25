<?php

declare(strict_types=1);

namespace App\Message;

use App\Message\Factories\MessageFactory;
use App\Message\Properties\Id;
use App\Message\Properties\UserId;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One message in a conversation. Private to the people in it, so not
 * audited.
 *
 * @property string $body
 * @property string $conversation_id
 * @property CarbonImmutable|null $created_at
 * @property string $id
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 * @property string|null $user_id
 */
#[ScopedBy(CurrentTeamScope::class)]
final class MessageEloquentModel extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function getTable(): string
    {
        return Table::Messages->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    /**
     * @return BelongsTo<UserEloquentModel, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(
            related: UserEloquentModel::class,
            foreignKey: UserId::getDatabaseTableColumnName(),
            relation: MessageRelationship::Author->value,
        );
    }

    protected static function newFactory(): MessageFactory
    {
        return MessageFactory::new();
    }
}
