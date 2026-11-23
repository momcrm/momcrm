<?php

declare(strict_types=1);

namespace App\LeaveRequest\Actions;

use App\LeaveRequest\LeaveRequest;
use App\LeaveRequest\LeaveRequestEloquentModel;
use App\LeaveRequest\LeaveStatusEnum;
use App\LeaveRequest\Properties\ReviewedAt;
use App\LeaveRequest\Properties\ReviewedBy;
use App\LeaveRequest\Properties\ReviewNote;
use App\LeaveRequest\Properties\Status;
use Carbon\CarbonImmutable;

/**
 * Approves, declines or cancels a request. Approving and declining record
 * who did it, when, and their note. The model is saved, so the audit log has
 * the change.
 */
final readonly class ChangeLeaveStatus
{
    public function __construct(
        private LeaveRequest $leave,
        private LeaveStatusEnum $status,
        private ?string $reviewerId = null,
        private ?string $note = null,
    ) {}

    public function __invoke(): LeaveRequest
    {
        $changes = [Status::getDatabaseTableColumnName() => $this->status->value];

        if (in_array($this->status, [LeaveStatusEnum::Approved, LeaveStatusEnum::Declined], true)) {
            $changes[ReviewedBy::getDatabaseTableColumnName()] = $this->reviewerId;
            $changes[ReviewedAt::getDatabaseTableColumnName()] = CarbonImmutable::now()->utc()->toDateTimeString();
            $changes[ReviewNote::getDatabaseTableColumnName()] = $this->note;
        }

        $model = LeaveRequestEloquentModel::query()->withoutGlobalScopes()->findOrFail($this->leave->getId()->toString());
        $model->update($changes);

        return LeaveRequest::fromEloquentModel($model->refresh());
    }

    public static function to(LeaveRequest $leave, LeaveStatusEnum $status, ?string $reviewerId = null, ?string $note = null): LeaveRequest
    {
        return new self($leave, $status, $reviewerId, $note)->handle();
    }

    public function handle(): LeaveRequest
    {
        return $this->__invoke();
    }

    public function execute(): LeaveRequest
    {
        return $this->__invoke();
    }
}
