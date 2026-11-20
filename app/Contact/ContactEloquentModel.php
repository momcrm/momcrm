<?php

declare(strict_types=1);

namespace App\Contact;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Contact\Factories\ContactFactory;
use App\Contact\Properties\CustomerId;
use App\Contact\Properties\Id;
use App\Contact\Properties\IsPrimary;
use App\Contact\Properties\ReceivesInvoices;
use App\Customer\CustomerEloquentModel;
use App\CustomField\Properties\CustomFieldValues;
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
 * @property CarbonImmutable|null $created_at
 * @property string $customer_id
 * @property string|null $email
 * @property string $first_name
 * @property string $id
 * @property bool $is_primary
 * @property bool $receives_invoices
 * @property string|null $last_name
 * @property string|null $mobile_phone
 * @property string|null $position
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 * @property string|null $work_phone
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class ContactEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::Contacts->value;
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
            relation: ContactRelationship::Customer->value,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            CustomFieldValues::getDatabaseTableColumnName() => 'array',
            IsPrimary::getDatabaseTableColumnName() => 'boolean',
            ReceivesInvoices::getDatabaseTableColumnName() => 'boolean',
        ];
    }

    protected static function newFactory(): ContactFactory
    {
        return ContactFactory::new();
    }
}
