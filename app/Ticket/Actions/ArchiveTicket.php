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
 * Moves a ticket to the archive, or brings it back. Archived tickets drop out
 * of every list and count until restored.
 */
final readonly class ArchiveTicket
{
    public function __construct(
        private Ticket $ticket,
        private bool $archive = true,
        private ?string $userId = null,
    ) {}

    public function __invoke(): Ticket
    {
        $model = TicketEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->ticket->getId()->toString());

        if ($model === null || $this->ticket->isArchived() === $this->archive) {
            return $this->ticket;
        }

        $this->ticket->setArchivedAt($this->archive ? CarbonImmutable::now() : null);
        $model->update($this->ticket->forDatabaseUpdate());

        RecordActivity::for(
            type: $this->archive ? ActivityTypeEnum::TicketArchived : ActivityTypeEnum::TicketRestored,
            userId: $this->userId,
            teamId: $this->ticket->getTeamId()->toString(),
            subject: $model,
            details: ['number' => $this->ticket->getNumber()->toInteger()],
        );

        return $this->ticket;
    }

    public static function archive(Ticket $ticket, ?string $userId = null): Ticket
    {
        return new self($ticket, archive: true, userId: $userId)->handle();
    }

    public static function restore(Ticket $ticket, ?string $userId = null): Ticket
    {
        return new self($ticket, archive: false, userId: $userId)->handle();
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
