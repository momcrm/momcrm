<?php

declare(strict_types=1);

namespace App\Timesheet\Actions;

use App\Timesheet\Properties\ReviewedAt;
use App\Timesheet\Properties\ReviewedBy;
use App\Timesheet\Properties\ReviewNote;
use App\Timesheet\Properties\Status;
use App\Timesheet\Properties\Totals;
use App\Timesheet\Timesheet;
use App\Timesheet\TimesheetEloquentModel;
use App\Timesheet\TimesheetStatusEnum;
use App\Timesheet\TimesheetTotals;
use Carbon\CarbonImmutable;

/**
 * Approves a timesheet, keeping its totals as they are now so later changes
 * to schedules or leave don't alter what was approved, or sends it back with
 * a note.
 */
final readonly class ReviewTimesheet
{
    public function __construct(
        private Timesheet $timesheet,
        private TimesheetStatusEnum $decision,
        private string $reviewerId,
        private ?string $note,
    ) {}

    public function __invoke(): Timesheet
    {
        $model = TimesheetEloquentModel::query()->withoutGlobalScopes()->findOrFail($this->timesheet->getId()->toString());

        $model->update([
            Status::getDatabaseTableColumnName() => $this->decision->value,
            ReviewedBy::getDatabaseTableColumnName() => $this->reviewerId,
            ReviewedAt::getDatabaseTableColumnName() => CarbonImmutable::now()->utc()->toDateTimeString(),
            ReviewNote::getDatabaseTableColumnName() => $this->note,
            Totals::getDatabaseTableColumnName() => $this->decision === TimesheetStatusEnum::Approved
                ? TimesheetTotals::live($model->team_id, $model->user_id, $this->timesheet->getStartsOn()->toDateString(), $this->timesheet->getEndsOn()->toDateString())
                : null,
        ]);

        return Timesheet::fromEloquentModel($model->refresh());
    }

    public static function decide(Timesheet $timesheet, TimesheetStatusEnum $decision, string $reviewerId, ?string $note = null): Timesheet
    {
        return new self($timesheet, $decision, $reviewerId, $note)->handle();
    }

    public function handle(): Timesheet
    {
        return $this->__invoke();
    }

    public function execute(): Timesheet
    {
        return $this->__invoke();
    }
}
