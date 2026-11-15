<?php

declare(strict_types=1);

namespace App\Project\Actions;

use App\AccessRule\Actions\PruneDeletedRecordsFromAccessRules;
use App\Attachment\Actions\DeleteAttachments;
use App\Project\Project;
use App\Project\ProjectEloquentModel;
use App\Shared\MorphMapKeyEnum;
use App\Team\Scopes\CurrentTeamScope;
use App\Ticket\Properties\ProjectId;
use App\Ticket\TicketEloquentModel;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a project for good, with its tickets, milestones and files. Each
 * ticket is deleted on its own so the audit trail records it. Access rule
 * conditions that named the project are tidied up.
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
                ->each(function (TicketEloquentModel $ticket): void {
                    DeleteAttachments::for(MorphMapKeyEnum::Ticket, $ticket->id);
                    $ticket->delete();
                });

            DeleteAttachments::for(MorphMapKeyEnum::Project, $this->project->getId()->toString());

            ProjectEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->find($this->project->getId()->toString())
                ?->delete();

            PruneDeletedRecordsFromAccessRules::forTeam($this->project->getTeamId()->toString());
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
