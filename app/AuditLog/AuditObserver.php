<?php

declare(strict_types=1);

namespace App\AuditLog;

use App\AuditLog\Actions\RecordAudit;
use App\AuditLog\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * Records created, updated and deleted events of Auditable models.
 *
 * Only model events are seen: database cascades and bulk query updates or
 * deletes do not fire them, so they leave no audit rows.
 */
final class AuditObserver
{
    public function created(Model&Auditable $model): void
    {
        RecordAudit::fromModel($model, AuditEventEnum::Created);
    }

    public function updated(Model&Auditable $model): void
    {
        RecordAudit::fromModel($model, AuditEventEnum::Updated);
    }

    public function deleted(Model&Auditable $model): void
    {
        RecordAudit::fromModel($model, AuditEventEnum::Deleted);
    }
}
