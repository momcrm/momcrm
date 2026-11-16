<?php

declare(strict_types=1);

namespace App\Ticket\FormRequests;

use App\Ticket\Actions\ArchiveTicket;
use App\Ticket\BulkTicketSelection;
use App\Ticket\Exceptions\CouldNotFindTicket;
use App\Ticket\Ticket;
use App\Ticket\TicketCollection;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Moves several tickets to the archive at once. If the person may not
 * archive any one of them, none move.
 */
final class BulkArchiveTicketsFormRequest extends FormRequest
{
    public TicketCollection $tickets;

    public function __construct()
    {
        parent::__construct();

        $this->tickets = new TicketCollection([]);
    }

    /**
     * @throws CouldNotFindTicket
     */
    public function authorize(): Response
    {
        $this->tickets = BulkTicketSelection::load($this);

        foreach ($this->tickets as $ticket) {
            Gate::authorize('archive', $ticket);
        }

        return Response::allow();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return BulkTicketSelection::rules();
    }

    public function process(): RedirectResponse
    {
        $userId = (string) $this->user()?->id;

        DB::transaction(function () use ($userId): void {
            $this->tickets->each(fn (Ticket $ticket): Ticket => ArchiveTicket::archive($ticket, userId: $userId));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => trans_choice('{1} 1 ticket archived.|[2,*] :count tickets archived.', $this->tickets->count())]);

        return back();
    }
}
