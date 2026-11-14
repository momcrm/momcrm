<?php

declare(strict_types=1);

namespace App\Onboarding\Actions;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Customer\CustomerEloquentModel;
use App\Customer\Properties\IsSample;
use App\Customer\Properties\TeamId;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a team's sample customers. Their contacts, locations, assets,
 * services, tickets and invoices go with them through cascading foreign keys,
 * which the audit trail does not see, so one activity entry records the removal.
 */
final readonly class RemoveSampleData
{
    public function __construct(
        private string $teamId,
        private string $userId,
    ) {}

    public function __invoke(): int
    {
        return DB::transaction(function (): int {
            $customers = CustomerEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->where(TeamId::getDatabaseTableColumnName(), $this->teamId)
                ->where(IsSample::getDatabaseTableColumnName(), true)
                ->get();

            $customers->each(fn (CustomerEloquentModel $customer): ?bool => $customer->delete());

            if ($customers->isNotEmpty()) {
                RecordActivity::for(
                    type: ActivityTypeEnum::SampleDataRemoved,
                    userId: $this->userId,
                    teamId: $this->teamId,
                    details: ['customers' => $customers->count()],
                );
            }

            return $customers->count();
        });
    }

    public static function forTeam(string $teamId, string $userId): int
    {
        return new self($teamId, $userId)->handle();
    }

    public function handle(): int
    {
        return $this->__invoke();
    }

    public function execute(): int
    {
        return $this->__invoke();
    }

    public static function teamHasSampleData(string $teamId): bool
    {
        return CustomerEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->where(IsSample::getDatabaseTableColumnName(), true)
            ->exists();
    }
}
