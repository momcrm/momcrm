<?php

declare(strict_types=1);

namespace App\TeamUser\Actions;

use App\TeamUser\Properties\TeamId;
use App\TeamUser\Properties\UserId;
use App\TeamUser\Properties\WorkScheduleId;
use App\TeamUser\TeamUserEloquentModel;

/**
 * Puts a member on a schedule, or back on the team's default with null.
 * The membership is saved as a model, so the audit log has the change.
 */
final readonly class AssignWorkSchedule
{
    public function __construct(
        private string $teamId,
        private string $userId,
        private ?string $workScheduleId,
    ) {}

    public function __invoke(): void
    {
        TeamUserEloquentModel::query()
            ->where(TeamId::getDatabaseTableColumnName(), $this->teamId)
            ->where(UserId::getDatabaseTableColumnName(), $this->userId)
            ->first()
            ?->update([WorkScheduleId::getDatabaseTableColumnName() => $this->workScheduleId]);
    }

    public static function forMember(string $teamId, string $userId, ?string $workScheduleId): void
    {
        new self($teamId, $userId, $workScheduleId)->handle();
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
