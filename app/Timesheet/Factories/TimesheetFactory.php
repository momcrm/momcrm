<?php

declare(strict_types=1);

namespace App\Timesheet\Factories;

use App\Shared\Types;
use App\Timesheet\Properties\EndsOn;
use App\Timesheet\Properties\StartsOn;
use App\Timesheet\Properties\Status;
use App\Timesheet\Properties\SubmittedAt;
use App\Timesheet\Properties\TeamId;
use App\Timesheet\Properties\UserId;
use App\Timesheet\TimesheetEloquentModel;
use App\Timesheet\TimesheetStatusEnum;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimesheetEloquentModel>
 */
final class TimesheetFactory extends Factory
{
    protected $model = TimesheetEloquentModel::class;

    /**
     * Last week's timesheet, submitted.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $monday = CarbonImmutable::today()->startOfWeek(CarbonImmutable::MONDAY)->subWeek();

        return [
            UserId::getDatabaseTableColumnName() => UserEloquentModel::factory()->withPersonalTeam(),
            TeamId::getDatabaseTableColumnName() => fn (array $attributes): string => Types::string(UserEloquentModel::query()
                ->whereKey($attributes[UserId::getDatabaseTableColumnName()])
                ->value('current_team_id')),
            StartsOn::getDatabaseTableColumnName() => $monday->toDateString(),
            EndsOn::getDatabaseTableColumnName() => $monday->addDays(6)->toDateString(),
            Status::getDatabaseTableColumnName() => TimesheetStatusEnum::Submitted->value,
            SubmittedAt::getDatabaseTableColumnName() => now(),
        ];
    }

    public function forUser(UserEloquentModel $user): self
    {
        return $this->state([
            UserId::getDatabaseTableColumnName() => $user->id,
            TeamId::getDatabaseTableColumnName() => $user->current_team_id,
        ]);
    }

    /**
     * The week (or other period) starting on this Monday.
     */
    public function period(string $startsOn, int $days = 7): self
    {
        return $this->state([
            StartsOn::getDatabaseTableColumnName() => $startsOn,
            EndsOn::getDatabaseTableColumnName() => CarbonImmutable::parse($startsOn)->addDays($days - 1)->toDateString(),
        ]);
    }

    public function status(TimesheetStatusEnum $status): self
    {
        return $this->state([Status::getDatabaseTableColumnName() => $status->value]);
    }

    public function submitted(): self
    {
        return $this->status(TimesheetStatusEnum::Submitted);
    }

    public function approved(): self
    {
        return $this->status(TimesheetStatusEnum::Approved);
    }
}
