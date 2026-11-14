<?php

declare(strict_types=1);

namespace App\Project\Actions;

use App\Project\Project;
use App\Project\ProjectEloquentModel;
use App\Team\Scopes\CurrentTeamScope;
use App\Ticket\Properties\ProjectId;
use App\Ticket\TicketEloquentModel;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a project for good, with its tickets and milestones. Each ticket is
 * deleted on its own so the audit trail records it.
 */
final readonly class DeleteProject
{
    public function __construct(
        private Project $project,
    ) {}

    public function __invoke(): void
    {
        DB::transaction(function (): void {
            TicketEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->where(ProjectId::getDatabaseTableColumnName(), $this->project->getId()->toString())
                ->get()
                ->each(fn (TicketEloquentModel $ticket): ?bool => $ticket->delete());

            ProjectEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->find($this->project->getId()->toString())
                ?->delete();
        });
    }

    public static function fromProject(Project $project): void
    {
        new self($project)->handle();
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
