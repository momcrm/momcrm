<?php

declare(strict_types=1);

namespace App\LeaveRequest\Factories;

use App\Choice\ChoiceEloquentModel;
use App\Choice\ChoiceListEnum;
use App\LeaveRequest\LeavePartEnum;
use App\LeaveRequest\LeaveRequestEloquentModel;
use App\LeaveRequest\LeaveStatusEnum;
use App\LeaveRequest\Properties\EndsOn;
use App\LeaveRequest\Properties\LeaveTypeId;
use App\LeaveRequest\Properties\Part;
use App\LeaveRequest\Properties\StartsOn;
use App\LeaveRequest\Properties\Status;
use App\LeaveRequest\Properties\TeamId;
use App\LeaveRequest\Properties\UserId;
use App\Shared\Types;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequestEloquentModel>
 */
final class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequestEloquentModel::class;

    /**
     * A pending full day of the team's default leave type, a week from today.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $day = now()->addWeek()->toDateString();

        return [
            UserId::getDatabaseTableColumnName() => UserEloquentModel::factory()->withPersonalTeam(),
            TeamId::getDatabaseTableColumnName() => fn (array $attributes): string => Types::string(UserEloquentModel::query()
                ->whereKey($attributes[UserId::getDatabaseTableColumnName()])
                ->value('current_team_id')),
            LeaveTypeId::getDatabaseTableColumnName() => fn (array $attributes): string => Types::string(ChoiceEloquentModel::query()
                ->withoutGlobalScopes()
                ->where('team_id', $attributes[TeamId::getDatabaseTableColumnName()])
                ->where('list', ChoiceListEnum::LeaveType->value)
                ->orderByDesc('is_default')
                ->value('id')),
            StartsOn::getDatabaseTableColumnName() => $day,
            EndsOn::getDatabaseTableColumnName() => $day,
            Part::getDatabaseTableColumnName() => LeavePartEnum::Full->value,
            Status::getDatabaseTableColumnName() => LeaveStatusEnum::Pending->value,
        ];
    }

    /**
     * Leave of this member in their current team.
     */
    public function forUser(UserEloquentModel $user): self
    {
        return $this->state([
            UserId::getDatabaseTableColumnName() => $user->id,
            TeamId::getDatabaseTableColumnName() => $user->current_team_id,
        ]);
    }

    public function between(string $startsOn, ?string $endsOn = null): self
    {
        return $this->state([
            StartsOn::getDatabaseTableColumnName() => $startsOn,
            EndsOn::getDatabaseTableColumnName() => $endsOn ?? $startsOn,
        ]);
    }

    public function part(LeavePartEnum $part): self
    {
        return $this->state([Part::getDatabaseTableColumnName() => $part->value]);
    }

    public function status(LeaveStatusEnum $status): self
    {
        return $this->state([Status::getDatabaseTableColumnName() => $status->value]);
    }

    public function approved(): self
    {
        return $this->status(LeaveStatusEnum::Approved);
    }
}
