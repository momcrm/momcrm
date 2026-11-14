<?php

declare(strict_types=1);

namespace App\Ticket\Actions;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Inbox\Actions\NotifyTicketWatchers;
use App\Team\Scopes\CurrentTeamScope;
use App\Ticket\Notifications\TicketAssignedNotification;
use App\Ticket\Notifications\TicketStatusChangedNotification;
use App\Ticket\Ticket;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;

/**
 * Saves a ticket. Moving it to or from Closed stamps or clears its closed time.
 * A new status or assignee is recorded in the team's activity, and the new
 * assignee and the ticket's watchers are notified.
 */
final readonly class UpdateTicket
{
    public function __construct(
        private Ticket $ticket,
        private ?string $updatedById = null,
    ) {}

    public function __invoke(): Ticket
    {
        $model = TicketEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->ticket->getId()->toString());

        if ($model === null) {
            return $this->ticket;
        }

        $before = Ticket::fromEloquentModel($model);
        $wasOpen = $before->isOpen();
        $isOpen = $this->ticket->isOpen();

        if ($wasOpen && ! $isOpen) {
            $this->ticket->setClosedAt(CarbonImmutable::now());
        } elseif (! $wasOpen && $isOpen) {
            $this->ticket->setClosedAt(null);
        }

        $model->update($this->ticket->forDatabaseUpdate());

        $teamId = $this->ticket->getTeamId()->toString();
        $details = ['number' => $this->ticket->getNumber()->toInteger(), 'title' => $this->ticket->getTitle()->toString()];
        $status = $this->ticket->getStatus()->toEnum();
        $statusChanged = ! $before->getStatus()->equalsTo($status->value);
        $assigneeId = $this->ticket->getAssigneeId()->toNullableString();
        $assigneeChanged = $before->getAssigneeId()->toNullableString() !== $assigneeId;
        $actor = $this->updatedById === null ? null : UserEloquentModel::query()->find($this->updatedById);

        if ($wasOpen !== $isOpen) {
            RecordActivity::for(
                type: $isOpen ? ActivityTypeEnum::TicketReopened : ActivityTypeEnum::TicketClosed,
                userId: $this->updatedById,
                teamId: $teamId,
                subject: $model,
                details: $details,
            );
        } elseif ($statusChanged) {
            RecordActivity::for(
                type: ActivityTypeEnum::TicketStatusChanged,
                userId: $this->updatedById,
                teamId: $teamId,
                subject: $model,
                details: [...$details, 'from' => $before->getStatus()->toEnum()->label(), 'to' => $status->label()],
            );
        }

        if ($assigneeChanged && $assigneeId !== null) {
            RecordActivity::for(
                type: ActivityTypeEnum::TicketAssigned,
                userId: $this->updatedById,
                teamId: $teamId,
                subject: $model,
                details: [...$details, 'assignee' => UserEloquentModel::query()->find($assigneeId)?->name],
            );

            if ($assigneeId !== $this->updatedById) {
                UserEloquentModel::query()->find($assigneeId)?->notify(new TicketAssignedNotification($model->refresh(), $actor));
            }
        }

        if ($statusChanged) {
            NotifyTicketWatchers::send(
                $model->refresh(),
                new TicketStatusChangedNotification($model, $actor, $status->label()),
                actorId: $this->updatedById,
                except: $assigneeChanged ? [$assigneeId] : [],
            );
        }

        return $this->ticket;
    }

    public static function fromTicket(Ticket $ticket, ?string $updatedById = null): Ticket
    {
        return new self($ticket, $updatedById)->handle();
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
