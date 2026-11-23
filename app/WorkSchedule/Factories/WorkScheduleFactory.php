<?php

declare(strict_types=1);

namespace App\WorkSchedule\Factories;

use App\Team\TeamEloquentModel;
use App\WorkSchedule\Properties\Days;
use App\WorkSchedule\Properties\GraceMinutes;
use App\WorkSchedule\Properties\IsDefault;
use App\WorkSchedule\Properties\Name;
use App\WorkSchedule\Properties\TeamId;
use App\WorkSchedule\WorkScheduleEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkScheduleEloquentModel>
 */
final class WorkScheduleFactory extends Factory
{
    protected $model = WorkScheduleEloquentModel::class;

    /**
     * Monday to Friday, 9 to 5 with a 30-minute break and 5 minutes' grace.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $day = ['starts_at' => '09:00', 'ends_at' => '17:00', 'break_minutes' => 30];

        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            Name::getDatabaseTableColumnName() => 'Office hours',
            IsDefault::getDatabaseTableColumnName() => false,
            Days::getDatabaseTableColumnName() => ['mon' => $day, 'tue' => $day, 'wed' => $day, 'thu' => $day, 'fri' => $day, 'sat' => null, 'sun' => null],
            GraceMinutes::getDatabaseTableColumnName() => 5,
        ];
    }

    public function forTeam(string $teamId): self
    {
        return $this->state([TeamId::getDatabaseTableColumnName() => $teamId]);
    }

    /**
     * The schedule members without one of their own follow.
     */
    public function teamDefault(): self
    {
        return $this->state([IsDefault::getDatabaseTableColumnName() => true]);
    }

    /**
     * Every weekday with the same hours; the weekend stays off.
     */
    public function weekdays(string $startsAt, string $endsAt, int $breakMinutes = 0): self
    {
        $day = ['starts_at' => $startsAt, 'ends_at' => $endsAt, 'break_minutes' => $breakMinutes];

        return $this->state([Days::getDatabaseTableColumnName() => ['mon' => $day, 'tue' => $day, 'wed' => $day, 'thu' => $day, 'fri' => $day, 'sat' => null, 'sun' => null]]);
    }
}
