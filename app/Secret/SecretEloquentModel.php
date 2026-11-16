<?php

declare(strict_types=1);

namespace App\Secret;

use App\Asset\AssetEloquentModel;
use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Customer\CustomerEloquentModel;
use App\Secret\Factories\SecretFactory;
use App\Secret\Properties\AssetId;
use App\Secret\Properties\CustomerId;
use App\Secret\Properties\Id;
use App\Secret\Properties\Version;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The values are encrypted with the app key and hidden, so they stay out of
 * arrays, JSON and the audit log.
 *
 * @property string|null $asset_id
 * @property CarbonImmutable|null $created_at
 * @property string $customer_id
 * @property string|null $description
 * @property string $id
 * @property string $team_id
 * @property string $title
 * @property CarbonImmutable|null $updated_at
 * @property list<array{key: string, value: string}> $values
 * @property int $version
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class SecretEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<SecretFactory> */
    use HasFactory;

    use HasUuids;

    public const string VALUES = 'values';

    protected $guarded = [];

    protected $hidden = [self::VALUES];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::Secrets->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    /**
     * @return BelongsTo<CustomerEloquentModel, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(
            related: CustomerEloquentModel::class,
            foreignKey: CustomerId::getDatabaseTableColumnName(),
            relation: SecretRelationship::Customer->value,
        );
    }

    /**
     * @return BelongsTo<AssetEloquentModel, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(
            related: AssetEloquentModel::class,
            foreignKey: AssetId::getDatabaseTableColumnName(),
            relation: SecretRelationship::Asset->value,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            self::VALUES => 'encrypted:array',
            Version::getDatabaseTableColumnName() => 'integer',
        ];
    }

    protected static function newFactory(): SecretFactory
    {
        return SecretFactory::new();
    }
}
