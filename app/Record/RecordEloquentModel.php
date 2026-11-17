<?php

declare(strict_types=1);

namespace App\Record;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\CustomField\Properties\CustomFieldValues;
use App\Record\Factories\RecordFactory;
use App\Record\Properties\Id;
use App\Record\Properties\Number;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CarbonImmutable|null $created_at
 * @property string|null $created_by_id
 * @property array<string, mixed>|null $custom_fields
 * @property string $id
 * @property string $name
 * @property int $number
 * @property string $object_id
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class RecordEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<RecordFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::Records->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    protected static function newFactory(): RecordFactory
    {
        return RecordFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            CustomFieldValues::getDatabaseTableColumnName() => 'array',
            Number::getDatabaseTableColumnName() => 'integer',
        ];
    }
}
