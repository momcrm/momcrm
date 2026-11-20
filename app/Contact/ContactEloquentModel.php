<?php

declare(strict_types=1);

namespace App\Contact;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Contact\Factories\ContactFactory;
use App\Contact\Properties\CustomerId;
use App\Contact\Properties\Id;
use App\Contact\Properties\IsPrimary;
use App\Contact\Properties\PortalAccess;
use App\Contact\Properties\ReceivesInvoices;
use App\Customer\CustomerEloquentModel;
use App\CustomField\Properties\CustomFieldValues;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $contact_role_id
 * @property CarbonImmutable|null $created_at
 * @property string $customer_id
 * @property string|null $email
 * @property string $first_name
 * @property string $id
 * @property bool $is_primary
 * @property bool $receives_invoices
 * @property bool $portal_access
 * @property CarbonImmutable|null $portal_invited_at
 * @property CarbonImmutable|null $portal_last_seen_at
 * @property string|null $remember_token
 * @property string|null $last_name
 * @property string|null $mobile_phone
 * @property string|null $position
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 * @property string|null $work_phone
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class ContactEloquentModel extends Model implements Auditable, AuthenticatableContract
{
    use Authenticatable;

    /** @use HasFactory<ContactFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    /**
     * @var list<string>
     */
    protected $hidden = ['remember_token'];

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
     * Contacts sign in to the portal with an emailed code or Google or
     * Microsoft, never a password. An empty password (not null) still lets
     * the remember-me cookie sign them back in.
     */
    public function getAuthPassword(): string
    {
        return '';
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
            PortalAccess::getDatabaseTableColumnName() => 'boolean',
            'portal_last_seen_at' => 'immutable_datetime',
            'portal_invited_at' => 'immutable_datetime',
        ];
    }

    protected static function newFactory(): ContactFactory
    {
        return ContactFactory::new();
    }
}
