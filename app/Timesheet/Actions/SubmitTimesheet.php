<?php

declare(strict_types=1);

namespace App\Timesheet\Actions;

use App\Timesheet\Properties\EndsOn;
use App\Timesheet\Properties\Id;
use App\Timesheet\Properties\ReviewedAt;
use App\Timesheet\Properties\ReviewedBy;
use App\Timesheet\Properties\ReviewNote;
use App\Timesheet\Properties\StartsOn;
use App\Timesheet\Properties\Status;
use App\Timesheet\Properties\SubmittedAt;
use App\Timesheet\Properties\TeamId;
use App\Timesheet\Properties\UserId;
use App\Timesheet\Timesheet;
use App\Timesheet\TimesheetEloquentModel;
use App\Timesheet\TimesheetStatusEnum;
use Carbon\CarbonImmutable;

/**
 * Sends a member's period for approval: a new timesheet, or one that was
 * sent back, submitted again with the last review cleared.
 */
final readonly class SubmitTimesheet
{
    public function __construct(
        private string $teamId,
        private string $userId,
        private string $startsOn,
        private string $endsOn,
    ) {}

    public function __invoke(): Timesheet
    {
        $values = [
            Status::getDatabaseTableColumnName() => TimesheetStatusEnum::Submitted->value,
            SubmittedAt::getDatabaseTableColumnName() => CarbonImmutable::now()->utc()->toDateTimeString(),
            ReviewedBy::getDatabaseTableColumnName() => null,
            ReviewedAt::getDatabaseTableColumnName() => null,
            ReviewNote::getDatabaseTableColumnName() => null,
        ];

        $model = TimesheetEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $this->teamId)
            ->where(UserId::getDatabaseTableColumnName(), $this->userId)
            ->where(StartsOn::getDatabaseTableColumnName(), $this->startsOn)
            ->first();

        if ($model instanceof TimesheetEloquentModel) {
            $model->update($values);
        } else {
            $model = TimesheetEloquentModel::query()->create([
                Id::getDatabaseTableColumnName() => Id::generate()->toString(),
                TeamId::getDatabaseTableColumnName() => $this->teamId,
                UserId::getDatabaseTableColumnName() => $this->userId,
                StartsOn::getDatabaseTableColumnName() => $this->startsOn,
                EndsOn::getDatabaseTableColumnName() => $this->endsOn,
                ...$values,
            ]);
        }

        return Timesheet::fromEloquentModel($model->refresh());
    }

    public static function forPeriod(string $teamId, string $userId, string $startsOn, string $endsOn): Timesheet
    {
        return new self($teamId, $userId, $startsOn, $endsOn)->handle();
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
