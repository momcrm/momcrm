<?php

declare(strict_types=1);

namespace App\BankAccount;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\BankAccount\Factories\BankAccountFactory;
use App\BankAccount\Properties\CsvColumns;
use App\BankAccount\Properties\Id;
use App\BankAccount\Properties\LastImportedAt;
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
 * @property array<string, mixed>|null $csv_columns
 * @property string $currency
 * @property string $id
 * @property string $kind
 * @property CarbonImmutable|null $last_imported_at
 * @property string $name
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class BankAccountEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<BankAccountFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::BankAccounts->value;
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
            CsvColumns::getDatabaseTableColumnName() => 'array',
            LastImportedAt::getDatabaseTableColumnName() => 'immutable_datetime',
        ];
    }

    protected static function newFactory(): BankAccountFactory
    {
        return BankAccountFactory::new();
    }
}
