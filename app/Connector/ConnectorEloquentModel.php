<?php

declare(strict_types=1);

namespace App\Connector;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Connector\Factories\ConnectorFactory;
use App\Connector\Properties\Id;
use App\Connector\Properties\Settings;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The credentials are encrypted with the app key and hidden, so they stay out
 * of arrays, JSON and the audit log.
 *
 * @property string $account
 * @property string|null $connected_by_id
 * @property CarbonImmutable|null $created_at
 * @property array<string, mixed> $credentials
 * @property string $id
 * @property string|null $last_error
 * @property string $name
 * @property array<string, mixed>|null $settings
 * @property string $status
 * @property string $team_id
 * @property string $type
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class ConnectorEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<ConnectorFactory> */
    use HasFactory;

    use HasUuids;

    public const string CREDENTIALS = 'credentials';

    /**
     * The credential a sign-in leaves us with, exchanged for access tokens.
     */
    public const string REFRESH_TOKEN = 'refresh_token';

    protected $guarded = [];

    protected $hidden = [self::CREDENTIALS];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::Connectors->value;
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
            self::CREDENTIALS => 'encrypted:array',
            Settings::getDatabaseTableColumnName() => 'array',
        ];
    }

    protected static function newFactory(): ConnectorFactory
    {
        return ConnectorFactory::new();
    }
}
