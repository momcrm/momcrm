<?php

declare(strict_types=1);

namespace App\AccessRule\Engine;

use App\Shared\RequestMemo;
use App\Shared\Table;
use App\Shared\Types;
use Illuminate\Support\Facades\DB;

/**
 * A customer's contact signed in to the portal, as contact rules see them:
 * who they are, their role and ticks, and what their customer is like.
 */
final readonly class ContactAccessSubject
{
    public function __construct(
        public string $contactId,
        public string $teamId,
        public string $customerId,
        public ?string $roleId,
        public bool $isPrimary,
        public bool $receivesInvoices,
        public bool $hasPortalAccess,
        public ?string $customerTypeId,
        public ?string $customerStatus,
    ) {}

    /**
     * Built once per request for each contact; null when the contact is gone.
     */
    public static function for(string $contactId): ?self
    {
        return RequestMemo::remember("contact-access-subject.{$contactId}", function () use ($contactId): ?self {
            $contacts = Table::Contacts->value;
            $customers = Table::Customers->value;

            $row = DB::table($contacts)
                ->join($customers, "{$customers}.id", '=', "{$contacts}.customer_id")
                ->where("{$contacts}.id", $contactId)
                ->first([
                    "{$contacts}.id",
                    "{$contacts}.team_id",
                    "{$contacts}.customer_id",
                    "{$contacts}.contact_role_id",
                    "{$contacts}.is_primary",
                    "{$contacts}.receives_invoices",
                    "{$contacts}.portal_access",
                    "{$customers}.type_id",
                    "{$customers}.status",
                ]);

            if ($row === null) {
                return null;
            }

            return new self(
                contactId: Types::string($row->id),
                teamId: Types::string($row->team_id),
                customerId: Types::string($row->customer_id),
                roleId: Types::nullableString($row->contact_role_id),
                isPrimary: (bool) $row->is_primary,
                receivesInvoices: (bool) $row->receives_invoices,
                hasPortalAccess: (bool) $row->portal_access,
                customerTypeId: Types::nullableString($row->type_id),
                customerStatus: Types::nullableString($row->status),
            );
        });
    }

    /**
     * Drops the remembered contact after it changes within the request.
     */
    public static function forget(string $contactId): void
    {
        RequestMemo::forget("contact-access-subject.{$contactId}");
    }
}
