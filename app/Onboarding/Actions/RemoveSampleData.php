<?php

declare(strict_types=1);

namespace App\Onboarding\Actions;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Article\Actions\DeleteArticle;
use App\Article\Article;
use App\Article\ArticleEloquentModel;
use App\Article\Properties\CustomerId as ArticleCustomerId;
use App\Customer\CustomerEloquentModel;
use App\Customer\Properties\IsSample;
use App\Customer\Properties\TeamId;
use App\Project\ProjectEloquentModel;
use App\Project\Properties\IsSample as ProjectIsSample;
use App\Project\Properties\TeamId as ProjectTeamId;
use App\Team\Scopes\CurrentTeamScope;
use App\Ticket\Properties\ProjectId as TicketProjectId;
use App\Ticket\TicketEloquentModel;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a team's sample projects (with their tickets) and sample customers.
 * The customers' articles are deleted first, with their pictures, as an
 * article only loses its customer when the customer goes. Their contacts,
 * locations, assets, services, tickets and invoices go with them through
 * cascading foreign keys, which the audit trail does not see, so one activity
 * entry records the removal.
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

            $projects = ProjectEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->where(ProjectTeamId::getDatabaseTableColumnName(), $this->teamId)
                ->where(ProjectIsSample::getDatabaseTableColumnName(), true)
                ->get();

            TicketEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->whereIn(TicketProjectId::getDatabaseTableColumnName(), $projects->modelKeys())
                ->get()
                ->each(fn (TicketEloquentModel $ticket): ?bool => $ticket->delete());

            ArticleEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->whereIn(ArticleCustomerId::getDatabaseTableColumnName(), $customers->modelKeys())
                ->get()
                ->each(fn (ArticleEloquentModel $article) => DeleteArticle::fromArticle(Article::fromEloquentModel($article)));

            $projects->each(fn (ProjectEloquentModel $project): ?bool => $project->delete());
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
