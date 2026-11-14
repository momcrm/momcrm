<?php

declare(strict_types=1);

namespace App\Ticket\Actions;

use App\Ticket\Properties\ArchivedAt;
use App\Ticket\Properties\Id;
use App\Ticket\Properties\Position;
use App\Ticket\Properties\Status;
use App\Ticket\Properties\TeamId;
use App\Ticket\Ticket;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketStatusEnum;
use Illuminate\Support\Facades\DB;

/**
 * Drops a ticket into a board column at a given place: changes its status if
 * the column is different, then renumbers the column so the order sticks.
 */
final readonly class MoveTicket
{
    public function __construct(
        private Ticket $ticket,
        private TicketStatusEnum $status,
        private int $position,
        private ?string $movedById = null,
    ) {}

    public function __invoke(): Ticket
    {
        return DB::transaction(function (): Ticket {
            if ($this->ticket->getStatus()->toEnum() !== $this->status) {
                UpdateTicket::fromTicket($this->ticket->setStatus($this->status), updatedById: $this->movedById);
            }

            $column = TicketEloquentModel::query()
                ->withoutGlobalScopes()
                ->where(TeamId::getDatabaseTableColumnName(), $this->ticket->getTeamId()->toString())
                ->where(Status::getDatabaseTableColumnName(), $this->status->value)
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

            return $this->ticket->setPosition($position);
        });
    }

    public static function to(Ticket $ticket, TicketStatusEnum $status, int $position, ?string $movedById = null): Ticket
    {
        return new self($ticket, $status, $position, $movedById)->handle();
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
