<?php

declare(strict_types=1);

namespace App\Portal\FormRequests;

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessResourceEnum;
use App\Choice\Actions\FindChoice;
use App\Choice\ChoiceListEnum;
use App\Comment\Actions\CreateComment;
use App\Comment\Comment;
use App\Comment\CommentVisibilityEnum;
use App\Comment\Properties\Body;
use App\Portal\Actions\FindPortalTickets;
use App\Portal\PortalAccess;
use App\Portal\PortalContact;
use App\Ticket\Actions\UpdateTicket;
use App\Ticket\Ticket;
use App\Ticket\TicketStatusCategoryEnum;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Adds the contact's reply to the ticket's conversation, where staff see it
 * as from the customer and its watchers are told. A reply to a closed ticket
 * reopens it, when the contact may reopen tickets.
 */
final class StorePortalTicketReplyFormRequest extends FormRequest
{
    public Ticket $ticket;

    public function __construct()
    {
        parent::__construct();

        $this->ticket = Ticket::new();
    }

    /**
     * @throws AuthorizationException
     */
    public function authorize(): bool
    {
        $this->ticket = FindPortalTickets::visibleToContact((string) $this->route('ticket'));

        PortalAccess::authorize(AccessResourceEnum::Comment, AccessActionEnum::Create);

        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            Body::getNameForValidation() => Body::getValidationRulesForCreate($this),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            Body::getNameForValidation().'.required' => __('Write your reply.'),
        ];
    }

    public function process(): RedirectResponse
    {
        $contact = PortalContact::current();
        $teamId = $this->ticket->getTeamId()->toString();

        DB::transaction(function () use ($contact, $teamId): void {
            CreateComment::fromComment(
                Comment::new()
                    ->setTeamId($teamId)
                    ->setTicketId($this->ticket->getId())
                    ->setContactId($contact->getId())
                    ->setBody($this->string(Body::getNameForValidation())->toString())
                    ->setVisibility(CommentVisibilityEnum::Public)
                    ->setSenderName($contact->getFullName())
                    ->setSenderEmail($contact->getEmail()),
            );

            $isClosed = FindChoice::byIdForTeam($this->ticket->getStatusId()->toNullableString(), $teamId)->hasCategory(TicketStatusCategoryEnum::Closed);
            $open = FindChoice::defaultForTeam($teamId, ChoiceListEnum::TicketStatus, TicketStatusCategoryEnum::Open->value);

            if ($isClosed && $open->isNotNull() && PortalAccess::allows(AccessResourceEnum::Ticket, AccessActionEnum::Reopen, $this->ticket)) {
                UpdateTicket::fromTicket($this->ticket->setStatusId($open->getId()), actorName: $contact->getFullName());
            }
        });

        return back();
    }
}
