<?php

declare(strict_types=1);

namespace App\ShiftBreak\Factories;

use App\Shared\Types;
use App\Shift\ShiftEloquentModel;
use App\ShiftBreak\Properties\EndedAt;
use App\ShiftBreak\Properties\ShiftId;
use App\ShiftBreak\Properties\StartedAt;
use App\ShiftBreak\Properties\TeamId;
use App\ShiftBreak\ShiftBreakEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftBreakEloquentModel>
 */
final class ShiftBreakFactory extends Factory
{
    protected $model = ShiftBreakEloquentModel::class;

    /**
     * A thirty-minute break from noon UTC today.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = CarbonImmutable::today('UTC')->setTime(12, 0);

        return [
            ShiftId::getDatabaseTableColumnName() => ShiftEloquentModel::factory(),
            TeamId::getDatabaseTableColumnName() => fn (array $attributes): string => Types::string(ShiftEloquentModel::query()
                ->withoutGlobalScopes()
                ->whereKey($attributes[ShiftId::getDatabaseTableColumnName()])
                ->value('team_id')),
            StartedAt::getDatabaseTableColumnName() => $startedAt->toDateTimeString(),
            EndedAt::getDatabaseTableColumnName() => $startedAt->addMinutes(30)->toDateTimeString(),
        ];
    }

    /**
     * A break during this shift, in its team.
     */
    public function forShift(ShiftEloquentModel $shift): self
    {
        return $this->state([
            ShiftId::getDatabaseTableColumnName() => $shift->id,
            TeamId::getDatabaseTableColumnName() => $shift->team_id,
        ]);
    }

    /**
     * Started at this moment and lasting these minutes, or still running when
     * no minutes are given.
     */
    public function between(CarbonImmutable $startedAt, ?int $minutes = null): self
    {
        $startedAt = $startedAt->utc();

        return $this->state([
            StartedAt::getDatabaseTableColumnName() => $startedAt->toDateTimeString(),
            EndedAt::getDatabaseTableColumnName() => $minutes === null ? null : $startedAt->addMinutes($minutes)->toDateTimeString(),
        ]);
    }
}
