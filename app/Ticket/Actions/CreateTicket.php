<?php

declare(strict_types=1);

namespace App\Ticket\Actions;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Choice\Actions\FindChoice;
use App\Choice\ChoiceListEnum;
use App\Project\Actions\FindProject;
use App\Shared\Actions\NextTeamNumber;
use App\Shared\Table;
use App\Ticket\Notifications\TicketAssignedNotification;
use App\Ticket\Properties\Id;
use App\Ticket\Properties\Position;
use App\Ticket\Properties\StatusId;
use App\Ticket\Properties\TeamId;
use App\Ticket\Ticket;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketStatusCategoryEnum;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\DB;

/**
 * Opens a ticket with the team's next number, at the bottom of its status
 * column, in the team's default open status and default priority unless
 * others are given. A project ticket with no customer takes the project's
 * customer. Someone other than the opener who is assigned it is notified.
 */
final readonly class CreateTicket
{
    public function __construct(
        private Ticket $ticket,
        private ?string $openedById = null,
    ) {}

    public function __invoke(): Ticket
    {
        return DB::transaction(function (): Ticket {
            if ($this->ticket->getId()->isNull()) {
                $this->ticket->setId(Id::generate());
            }

            $teamId = $this->ticket->getTeamId()->toString();

            if ($this->ticket->getStatusId()->isNull()) {
                $this->ticket->setStatusId(FindChoice::defaultForTeam($teamId, ChoiceListEnum::TicketStatus, TicketStatusCategoryEnum::Open->value)->getId());
            }

            if ($this->ticket->getCustomerId()->isNull() && $this->ticket->getProjectId()->isNotNull()) {
                $this->ticket->setCustomerId(
                    FindProject::byIdForTeam($this->ticket->getProjectId()->toString(), $teamId)->getCustomerId(),
                );
            }

            if ($this->ticket->getPriorityId()->isNull()) {
                $this->ticket->setPriorityId(FindChoice::defaultForTeam($teamId, ChoiceListEnum::TicketPriority)->getId());
            }

            if ($this->ticket->getPosition()->isNull()) {
                $this->ticket->setPosition(self::nextPosition($teamId, $this->ticket->getStatusId()->toString()));
            }

            $this->ticket->setNumber(NextTeamNumber::for(Table::Tickets, $teamId));

            $model = TicketEloquentModel::query()->create($this->ticket->forDatabaseCreate());

            RecordActivity::for(
                type: ActivityTypeEnum::TicketOpened,
                userId: $this->openedById,
                teamId: $this->ticket->getTeamId()->toString(),
                subject: $model,
                details: ['number' => $this->ticket->getNumber()->toInteger()],
            );

            $assigneeId = $this->ticket->getAssigneeId()->toNullableString();

            if ($assigneeId !== null && $assigneeId !== $this->openedById) {
                UserEloquentModel::query()->find($assigneeId)?->notify(new TicketAssignedNotification(
                    $model->refresh(),
                    $this->openedById === null ? null : UserEloquentModel::query()->find($this->openedById),
                ));
            }

            return Ticket::fromEloquentModel($model->refresh());
        });
    }

    private static function nextPosition(string $teamId, string $statusId): int
    {
        $highest = TicketEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->where(StatusId::getDatabaseTableColumnName(), $statusId)
            ->max(Position::getDatabaseTableColumnName());

        return is_numeric($highest) ? (int) $highest + 1 : 0;
    }

    public static function fromTicket(Ticket $ticket, ?string $openedById = null): Ticket
    {
        return new self($ticket, $openedById)->handle();
    }

    public function handle(): Ticket
    {
        return $this->__invoke();
    }

    public function execute(): Ticket
    {
        return $this->__invoke();
    }
}
