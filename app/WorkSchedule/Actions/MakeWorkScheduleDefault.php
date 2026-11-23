<?php

declare(strict_types=1);

namespace App\WorkSchedule\Actions;

use App\WorkSchedule\Properties\IsDefault;
use App\WorkSchedule\Properties\TeamId;
use App\WorkSchedule\WorkSchedule;
use App\WorkSchedule\WorkScheduleEloquentModel;
use Illuminate\Support\Facades\DB;

/**
 * Makes a schedule the one members without their own follow, in place of the
 * team's current default. Each model is saved, so the audit log has both.
 */
final readonly class MakeWorkScheduleDefault
{
    public function __construct(
        private WorkSchedule $schedule,
    ) {}

    public function __invoke(): void
    {
        DB::transaction(function (): void {
            WorkScheduleEloquentModel::query()
                ->withoutGlobalScopes()
                ->where(TeamId::getDatabaseTableColumnName(), $this->schedule->getTeamId()->toString())
                ->lockForUpdate()
                ->get()
                ->each(function (WorkScheduleEloquentModel $model): void {
                    $model->update([IsDefault::getDatabaseTableColumnName() => $model->id === $this->schedule->getId()->toString()]);
                });
        });
    }

    public static function fromWorkSchedule(WorkSchedule $schedule): void
    {
        new self($schedule)->handle();
    }

    public function handle(): void
    {
        $this->__invoke();
    }

    public function execute(): void
    {
        $this->__invoke();
    }
}
