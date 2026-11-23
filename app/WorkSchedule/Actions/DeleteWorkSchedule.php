<?php

declare(strict_types=1);

namespace App\WorkSchedule\Actions;

use App\WorkSchedule\WorkSchedule;
use App\WorkSchedule\WorkScheduleEloquentModel;

/**
 * Deletes a schedule. Members who worked to it follow the team's default
 * from then on (the foreign key sets theirs to null).
 */
final readonly class DeleteWorkSchedule
{
    public function __construct(
        private WorkSchedule $schedule,
    ) {}

    public function __invoke(): void
    {
        WorkScheduleEloquentModel::query()->withoutGlobalScopes()->find($this->schedule->getId()->toString())?->delete();
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
