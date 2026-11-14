<?php

declare(strict_types=1);

namespace App\Ticket\Actions;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Shared\Actions\NextTeamNumber;
use App\Shared\Table;
use App\Ticket\Properties\Id;
use App\Ticket\Ticket;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketStatusEnum;
use Illuminate\Support\Facades\DB;

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

            if ($this->ticket->getStatus()->isNull()) {
                $this->ticket->setStatus(TicketStatusEnum::New);
            }

            $this->ticket->setNumber(NextTeamNumber::for(Table::Tickets, $this->ticket->getTeamId()->toString()));

            $model = TicketEloquentModel::query()->create($this->ticket->forDatabaseCreate());

            RecordActivity::for(
                type: ActivityTypeEnum::TicketOpened,
                userId: $this->openedById,
                teamId: $this->ticket->getTeamId()->toString(),
                subject: $model,
                details: ['number' => $this->ticket->getNumber()->toInteger()],
            );

            return Ticket::fromEloquentModel($model->refresh());
        });
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
