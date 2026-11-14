<?php

declare(strict_types=1);

namespace App\EmailCode;

use App\EmailCode\Factories\EmailCodeFactory;
use App\EmailCode\Properties\Attempts;
use App\EmailCode\Properties\ConsumedAt;
use App\EmailCode\Properties\ExpiresAt;
use App\EmailCode\Properties\Id;
use App\Shared\Table;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $attempts
 * @property string $code_hash
 * @property CarbonImmutable|null $consumed_at
 * @property CarbonImmutable|null $created_at
 * @property string $email
 * @property CarbonImmutable|null $expires_at
 * @property string $id
 * @property string $purpose
 * @property CarbonImmutable|null $updated_at
 */
#[Hidden(['code_hash'])]
final class EmailCodeEloquentModel extends Model
{
    /** @use HasFactory<EmailCodeFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function getTable(): string
    {
        return Table::EmailCodes->value;
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
            Attempts::getDatabaseTableColumnName() => 'integer',
            ExpiresAt::getDatabaseTableColumnName() => 'immutable_datetime',
            ConsumedAt::getDatabaseTableColumnName() => 'immutable_datetime',
        ];
    }

    protected static function newFactory(): EmailCodeFactory
    {
        return EmailCodeFactory::new();
    }
}
