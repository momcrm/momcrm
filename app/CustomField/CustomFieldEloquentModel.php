<?php

declare(strict_types=1);

namespace App\CustomField;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\CustomField\Factories\CustomFieldFactory;
use App\CustomField\Properties\Id;
use App\CustomField\Properties\Options;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CarbonImmutable|null $archived_at
 * @property CarbonImmutable|null $created_at
 * @property string|null $description
 * @property string $id
 * @property string $name
 * @property string|null $object_id
 * @property array<int, mixed> $options
 * @property int $position
 * @property string|null $relation
 * @property string $target
 * @property string $team_id
 * @property string $type
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class CustomFieldEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<CustomFieldFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::CustomFields->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    protected static function newFactory(): CustomFieldFactory
    {
        return CustomFieldFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            Options::getDatabaseTableColumnName() => 'array',
        ];
    }
}
