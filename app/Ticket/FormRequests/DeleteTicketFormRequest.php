<?php

declare(strict_types=1);

namespace App\Ticket\FormRequests;

use App\Attachment\Actions\DeleteAttachments;
use App\Shared\MorphMapKeyEnum;
use App\Ticket\Actions\FindTicket;
use App\Ticket\Exceptions\CouldNotFindTicket;
use App\Ticket\Ticket;
use App\Ticket\TicketEloquentModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class DeleteTicketFormRequest extends FormRequest
{
    public Ticket $ticket;

    public function __construct()
    {
        parent::__construct();

        $this->ticket = Ticket::new();
    }

    /**
     * @throws CouldNotFindTicket
     */
    public function authorize(): bool
    {
        $this->ticket = FindTicket::byIdForTeam((string) $this->route('ticket'), $this->user()?->current_team_id);

        if ($this->ticket->isNull() || ! $this->ticket->isArchived()) {
            throw CouldNotFindTicket::notFound();
        }

        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }

    public function process(): RedirectResponse
    {
        DeleteAttachments::for(MorphMapKeyEnum::Ticket, $this->ticket->getId()->toString());
        TicketEloquentModel::query()->findOrFail($this->ticket->getId()->toString())->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Ticket #:number deleted.', ['number' => $this->ticket->getNumber()->toInteger()])]);

        return back();
    }
}
