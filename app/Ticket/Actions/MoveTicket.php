<?php

declare(strict_types=1);

namespace App\Ticket\Actions;

use App\Ticket\Events\TicketChanged;
use App\Ticket\Properties\ArchivedAt;
use App\Ticket\Properties\Id;
use App\Ticket\Properties\Position;
use App\Ticket\Properties\StatusId;
use App\Ticket\Properties\TeamId;
use App\Ticket\Ticket;
use App\Ticket\TicketEloquentModel;
use Illuminate\Support\Facades\DB;

/**
 * Drops a ticket into a board column at a given place: changes its status if
 * the column is different, then renumbers the column so the order sticks.
 */
final readonly class MoveTicket
{
    public function __construct(
        private Ticket $ticket,
        private string $statusId,
        private int $position,
        private ?string $movedById = null,
    ) {}

    public function __invoke(): Ticket
    {
        return DB::transaction(function (): Ticket {
            if ($this->ticket->getStatusId()->toNullableString() !== $this->statusId) {
                UpdateTicket::fromTicket($this->ticket->setStatusId($this->statusId), updatedById: $this->movedById);
            }

            $column = TicketEloquentModel::query()
                ->withoutGlobalScopes()
                ->where(TeamId::getDatabaseTableColumnName(), $this->ticket->getTeamId()->toString())
                ->where(StatusId::getDatabaseTableColumnName(), $this->statusId)
                ->whereNull(ArchivedAt::getDatabaseTableColumnName())
                ->whereKeyNot($this->ticket->getId()->toString())
                ->orderBy(Position::getDatabaseTableColumnName())
                ->orderByDesc('number')
                ->lockForUpdate()
                ->get([Id::getDatabaseTableColumnName(), Position::getDatabaseTableColumnName()])
                ->all();

            $position = max(0, min($this->position, count($column)));
            array_splice($column, $position, 0, [null]);

            foreach ($column as $index => $model) {
                $id = $model === null ? $this->ticket->getId()->toString() : $model->id;

                if ($model === null || $model->position !== $index) {
                    TicketEloquentModel::query()
                        ->withoutGlobalScopes()
                        ->whereKey($id)
                        ->update([Position::getDatabaseTableColumnName() => $index]);
                }
            }

            // Positions are saved with query updates, which fire no model events.
            TicketChanged::later($this->ticket->getTeamId()->toString(), $this->ticket->getId()->toString());

            return $this->ticket->setPosition($position);
        });
    }

    public static function to(Ticket $ticket, string $statusId, int $position, ?string $movedById = null): Ticket
    {
        return new self($ticket, $statusId, $position, $movedById)->handle();
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
