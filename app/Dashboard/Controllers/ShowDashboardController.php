<?php

declare(strict_types=1);

namespace App\Dashboard\Controllers;

use App\Customer\CustomerEloquentModel;
use App\Customer\Properties\TeamId as CustomerTeamId;
use App\Dashboard\WorkspaceNumbers;
use App\Invoice\Actions\FindInvoice;
use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceStatusEnum;
use App\Project\Actions\FindProject;
use App\Project\Controllers\ListProjectsController;
use App\Project\ProjectEloquentModel;
use App\Project\ProjectStatusEnum;
use App\Team\Scopes\CurrentTeamScope;
use App\Ticket\Actions\FindTicket;
use App\Ticket\Controllers\ListTicketsController;
use App\Ticket\TicketDrawer;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Home: a greeting, the team's headline numbers, the person's tickets, what's
 * due this week, active projects and the latest activity, plus the
 * getting-started checklist while it applies.
 */
final class ShowDashboardController
{
    public function __invoke(Request $request): Response
    {
        /** @var UserEloquentModel $user */
        $user = $request->user();
        $teamId = (string) $user->current_team_id;

        $unpaid = FindInvoice::paginateForTeam($teamId, InvoiceStatusEnum::Sent, perPage: 1000)
            ->getCollection()
            ->map(fn (InvoiceEloquentModel $model): array => FindInvoice::listItem($model));

        return Inertia::render('dashboard', [
            'preferredName' => $user->name,
            'stats' => [
                ...WorkspaceNumbers::headline($teamId),
                'customers' => CustomerEloquentModel::query()->withoutGlobalScope(CurrentTeamScope::class)->where(CustomerTeamId::getDatabaseTableColumnName(), $teamId)->count(),
                'unpaid_invoices' => $unpaid->count(),
                'unpaid_total_in_cents' => (int) $unpaid->sum('total_in_cents'),
                'overdue_invoices' => $unpaid->where('is_overdue', true)->count(),
            ],
            'myTickets' => FindTicket::assignedTo($teamId, $user->id)
                ->map(fn (TicketEloquentModel $model): array => ListTicketsController::listItem($model))
                ->values()
                ->all(),
            'deadlines' => WorkspaceNumbers::deadlines($teamId),
            'projects' => FindProject::listForTeam($teamId, $user->id, sort: 'due_on')
                ->reject(fn (ProjectEloquentModel $project): bool => $project->status === ProjectStatusEnum::Completed->value)
                ->take(6)
                ->map(fn (ProjectEloquentModel $model): array => ListProjectsController::listItem($model, []))
                ->values()
                ->all(),
            'activity' => WorkspaceNumbers::recentActivity($teamId, $user->id),
            ...TicketDrawer::options($teamId),
            'drawer' => TicketDrawer::forRequest($request),
            'hasSecurity' => $user->two_factor_confirmed_at !== null || $user->passkeys()->exists(),
        ]);
    }
}
