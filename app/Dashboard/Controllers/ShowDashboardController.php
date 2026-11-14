<?php

declare(strict_types=1);

namespace App\Dashboard\Controllers;

use App\Customer\CustomerEloquentModel;
use App\Customer\Properties\TeamId as CustomerTeamId;
use App\Invoice\Actions\FindInvoice;
use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceStatusEnum;
use App\Team\Scopes\CurrentTeamScope;
use App\Ticket\Actions\FindTicket;
use App\Ticket\Controllers\ListTicketsController;
use App\Ticket\Properties\Status;
use App\Ticket\Properties\TeamId as TicketTeamId;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketStatusEnum;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
                'customers' => CustomerEloquentModel::query()->withoutGlobalScope(CurrentTeamScope::class)->where(CustomerTeamId::getDatabaseTableColumnName(), $teamId)->count(),
                'open_tickets' => TicketEloquentModel::query()->withoutGlobalScope(CurrentTeamScope::class)
                    ->where(TicketTeamId::getDatabaseTableColumnName(), $teamId)
                    ->whereIn(Status::getDatabaseTableColumnName(), TicketStatusEnum::openValues())
                    ->count(),
                'unpaid_invoices' => $unpaid->count(),
                'unpaid_total_in_cents' => (int) $unpaid->sum('total_in_cents'),
                'overdue_invoices' => $unpaid->where('is_overdue', true)->count(),
            ],
            'myTickets' => FindTicket::paginateForTeam($teamId, perPage: 5, assigneeId: $user->id)
                ->getCollection()
                ->map(fn (TicketEloquentModel $model): array => ListTicketsController::listItem($model))
                ->all(),
            'hasSecurity' => $user->two_factor_confirmed_at !== null || $user->passkeys()->exists(),
        ]);
    }
}
