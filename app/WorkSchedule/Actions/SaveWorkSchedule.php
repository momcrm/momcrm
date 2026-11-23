<?php

declare(strict_types=1);

namespace App\WorkSchedule\Actions;

use App\WorkSchedule\Properties\Id;
use App\WorkSchedule\Properties\TeamId;
use App\WorkSchedule\WorkSchedule;
use App\WorkSchedule\WorkScheduleEloquentModel;
use Illuminate\Support\Facades\DB;

/**
 * Adds or changes a schedule. A team's first schedule becomes its default,
 * so members always have hours to be measured against once there are any.
 */
final readonly class SaveWorkSchedule
{
    public function __construct(
        private WorkSchedule $schedule,
    ) {}

    public function __invoke(): WorkSchedule
    {
        return DB::transaction(function (): WorkSchedule {
            if ($this->schedule->getId()->isNotNull()) {
                $model = WorkScheduleEloquentModel::query()->withoutGlobalScopes()->findOrFail($this->schedule->getId()->toString());
                $model->update($this->schedule->forDatabaseUpdate());

                return WorkSchedule::fromEloquentModel($model->refresh());
            }

            $isFirst = WorkScheduleEloquentModel::query()
                ->withoutGlobalScopes()
                ->where(TeamId::getDatabaseTableColumnName(), $this->schedule->getTeamId()->toString())
                ->doesntExist();

            $this->schedule->setId(Id::generate())->setIsDefault($isFirst);
            $model = WorkScheduleEloquentModel::query()->create($this->schedule->forDatabaseCreate());

            return WorkSchedule::fromEloquentModel($model->refresh());
        });
    }

    public static function fromWorkSchedule(WorkSchedule $schedule): WorkSchedule
    {
        return new self($schedule)->handle();
    }

    public function handle(): WorkSchedule
    {
        return $this->__invoke();
    }

    public function execute(): WorkSchedule
    {
        return $this->__invoke();
    }
}
