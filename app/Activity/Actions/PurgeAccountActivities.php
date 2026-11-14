<?php

declare(strict_types=1);

namespace App\Activity\Actions;

use App\Activity\ActivityEloquentModel;
use App\Activity\Properties\SubjectId;
use App\Activity\Properties\SubjectType;
use App\Activity\Properties\TeamId;
use App\Activity\Properties\UserId;
use App\Shared\MorphMapKeyEnum;
use Illuminate\Database\Eloquent\Builder;

final readonly class PurgeAccountActivities
{
    public function __construct(
        private string $userId,
    ) {}

    /**
     * Delete a user's account-level activity (not tied to a team) and any
     * activity about them. Team activity is kept; its actor is nulled by the FK.
     */
    public function __invoke(): int
    {
        return ActivityEloquentModel::query()
            ->where(fn (Builder $query) => $query
                ->where(UserId::getDatabaseTableColumnName(), $this->userId)
                ->whereNull(TeamId::getDatabaseTableColumnName()))
            ->orWhere(fn (Builder $query) => $query
                ->where(SubjectType::getDatabaseTableColumnName(), MorphMapKeyEnum::User->value)
                ->where(SubjectId::getDatabaseTableColumnName(), $this->userId))
            ->delete();
    }

    public static function fromUserId(string $userId): int
    {
        return new self($userId)->handle();
    }

    public function handle(): int
    {
        return $this->__invoke();
    }

    public function execute(): int
    {
        return $this->__invoke();
    }
}
