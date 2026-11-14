<?php

declare(strict_types=1);

namespace App\AuditLog\Contracts;

/**
 * An EloquentModel whose changes are recorded in the audit log.
 *
 * Attach App\AuditLog\AuditObserver with #[ObservedBy] alongside this interface.
 */
interface Auditable
{
    /**
     * The team the record belongs to, or null for account-level records.
     */
    public function auditTeamId(): ?string;
}
