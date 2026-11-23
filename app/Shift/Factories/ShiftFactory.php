<?php

declare(strict_types=1);

namespace App\Shift\Factories;

use App\Shared\Types;
use App\Shift\Properties\ClockedInAt;
use App\Shift\Properties\ClockedOutAt;
use App\Shift\Properties\TeamId;
use App\Shift\Properties\UserId;
use App\Shift\Properties\WorkDate;
use App\Shift\ShiftEloquentModel;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftEloquentModel>
 */
final class ShiftFactory extends Factory
{
    protected $model = ShiftEloquentModel::class;

    /**
     * A finished eight-hour shift that started at 9am UTC today.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $clockedInAt = CarbonImmutable::today('UTC')->setTime(9, 0);

        return [
            UserId::getDatabaseTableColumnName() => UserEloquentModel::factory()->withPersonalTeam(),
            TeamId::getDatabaseTableColumnName() => fn (array $attributes): string => Types::string(UserEloquentModel::query()
                ->whereKey($attributes[UserId::getDatabaseTableColumnName()])
                ->value('current_team_id')),
            WorkDate::getDatabaseTableColumnName() => $clockedInAt->toDateString(),
            ClockedInAt::getDatabaseTableColumnName() => $clockedInAt->toDateTimeString(),
            ClockedOutAt::getDatabaseTableColumnName() => $clockedInAt->addHours(8)->toDateTimeString(),
        ];
    }

    /**
     * A shift of this member in their current team.
     */
    public function forUser(UserEloquentModel $user): self
    {
        return $this->state([
            UserId::getDatabaseTableColumnName() => $user->id,
            TeamId::getDatabaseTableColumnName() => $user->current_team_id,
        ]);
    }

    /**
     * Clocked in at this moment and running for these hours, or still open
     * when no hours are given.
     */
    public function between(CarbonImmutable $clockedInAt, ?float $hours = null): self
    {
        $clockedInAt = $clockedInAt->utc();

        return $this->state([
            WorkDate::getDatabaseTableColumnName() => $clockedInAt->toDateString(),
            ClockedInAt::getDatabaseTableColumnName() => $clockedInAt->toDateTimeString(),
            ClockedOutAt::getDatabaseTableColumnName() => $hours === null ? null : $clockedInAt->addMinutes((int) round($hours * 60))->toDateTimeString(),
        ]);
    }

    /**
     * Clocked in an hour ago and not clocked out.
     */
    public function open(): self
    {
        return $this->between(CarbonImmutable::now()->subHour());
    }
}
