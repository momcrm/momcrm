<?php

declare(strict_types=1);

namespace App\SocialAccount;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Shared\Table;
use App\SocialAccount\Factories\SocialAccountFactory;
use App\SocialAccount\Properties\Id;
use App\SocialAccount\Properties\UserId;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CarbonImmutable|null $created_at
 * @property string|null $email
 * @property string $id
 * @property string $provider
 * @property string $provider_user_id
 * @property CarbonImmutable|null $updated_at
 * @property string $user_id
 */
#[ObservedBy(AuditObserver::class)]
final class SocialAccountEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<SocialAccountFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): ?string
    {
        return null;
    }

    public function getTable(): string
    {
        return Table::SocialAccounts->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    /**
     * @return BelongsTo<UserEloquentModel, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            related: UserEloquentModel::class,
            foreignKey: UserId::getDatabaseTableColumnName(),
            relation: SocialAccountRelationship::User->value,
        );
    }

    protected static function newFactory(): SocialAccountFactory
    {
        return SocialAccountFactory::new();
    }
}
