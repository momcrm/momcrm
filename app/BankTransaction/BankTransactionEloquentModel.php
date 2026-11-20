<?php

declare(strict_types=1);

namespace App\BankTransaction;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\BankAccount\BankAccountEloquentModel;
use App\BankTransaction\Factories\BankTransactionFactory;
use App\BankTransaction\Properties\AmountInCents;
use App\BankTransaction\Properties\BankAccountId;
use App\BankTransaction\Properties\CustomerId;
use App\BankTransaction\Properties\HiddenAt;
use App\BankTransaction\Properties\Id;
use App\BankTransaction\Properties\PaymentId;
use App\BankTransaction\Properties\PostedOn;
use App\Customer\CustomerEloquentModel;
use App\Payment\PaymentEloquentModel;
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
 * @property int $amount_in_cents
 * @property string $bank_account_id
 * @property CarbonImmutable|null $created_at
 * @property string|null $crn
 * @property string|null $customer_id
 * @property string $description
 * @property string $external_id
 * @property CarbonImmutable|null $hidden_at
 * @property string $id
 * @property string|null $matched_by
 * @property string|null $payer_name
 * @property string|null $payment_id
 * @property CarbonImmutable $posted_on
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class BankTransactionEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<BankTransactionFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::BankTransactions->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    /**
     * @return BelongsTo<BankAccountEloquentModel, $this>
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(
            related: BankAccountEloquentModel::class,
            foreignKey: BankAccountId::getDatabaseTableColumnName(),
            relation: BankTransactionRelationship::BankAccount->value,
        );
    }

    /**
     * @return BelongsTo<CustomerEloquentModel, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(
            related: CustomerEloquentModel::class,
            foreignKey: CustomerId::getDatabaseTableColumnName(),
            relation: BankTransactionRelationship::Customer->value,
        );
    }

    /**
     * @return BelongsTo<PaymentEloquentModel, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(
            related: PaymentEloquentModel::class,
            foreignKey: PaymentId::getDatabaseTableColumnName(),
            relation: BankTransactionRelationship::Payment->value,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            AmountInCents::getDatabaseTableColumnName() => 'integer',
            HiddenAt::getDatabaseTableColumnName() => 'immutable_datetime',
            PostedOn::getDatabaseTableColumnName() => 'immutable_date',
        ];
    }

    protected static function newFactory(): BankTransactionFactory
    {
        return BankTransactionFactory::new();
    }
}
