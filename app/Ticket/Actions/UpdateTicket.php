<?php

declare(strict_types=1);

namespace App\Ticket\Actions;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Team\Scopes\CurrentTeamScope;
use App\Ticket\Ticket;
use App\Ticket\TicketEloquentModel;
use Carbon\CarbonImmutable;

/**
 * Saves a ticket. Moving it to or from Closed stamps or clears its closed time
 * and records the change in the team's activity.
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

        $wasOpen = Ticket::fromEloquentModel($model)->isOpen();
        $isOpen = $this->ticket->isOpen();

        if ($wasOpen && ! $isOpen) {
            $this->ticket->setClosedAt(CarbonImmutable::now());
        } elseif (! $wasOpen && $isOpen) {
            $this->ticket->setClosedAt(null);
        }

        $model->update($this->ticket->forDatabaseUpdate());

        if ($wasOpen !== $isOpen) {
            RecordActivity::for(
                type: $isOpen ? ActivityTypeEnum::TicketReopened : ActivityTypeEnum::TicketClosed,
                userId: $this->updatedById,
                teamId: $this->ticket->getTeamId()->toString(),
                subject: $model,
                details: ['number' => $this->ticket->getNumber()->toInteger()],
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
