<?php

declare(strict_types=1);

namespace App\Comment\FormRequests;

use App\Comment\Actions\CreateComment;
use App\Comment\Comment;
use App\Comment\Properties\Body;
use App\Comment\Properties\Mentions;
use App\Shared\Table;
use App\Shared\Types;
use App\Ticket\Actions\FindTicket;
use App\Ticket\Exceptions\CouldNotFindTicket;
use App\Ticket\Ticket;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class StoreCommentFormRequest extends FormRequest
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
    public function authorize(): Response
    {
        $this->ticket = FindTicket::byIdForTeam((string) $this->route('ticket'), $this->user()?->current_team_id);

        if ($this->ticket->isNull()) {
            throw CouldNotFindTicket::notFound();
        }

        return Gate::authorize('create', Comment::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            Body::getNameForValidation() => Body::getValidationRulesForCreate($this),
            Mentions::getNameForValidation() => ['sometimes', 'array', 'max:20'],
            Mentions::getNameForValidation().'.*' => ['uuid', Rule::exists(Table::TeamUser->value, 'user_id')->where('team_id', $this->user()?->current_team_id)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            Body::getNameForValidation().'.required' => __('Write a comment first.'),
            Mentions::getNameForValidation().'.*.exists' => __('You can only mention people in your team.'),
        ];
    }

    public function process(): RedirectResponse
    {
        $mentions = array_values(array_unique(Types::stringList($this->validated()[Mentions::getNameForValidation()] ?? [])));

        CreateComment::fromComment(Comment::new()
            ->setTeamId($this->ticket->getTeamId())
            ->setTicketId($this->ticket->getId())
            ->setUserId((string) $this->user()?->id)
            ->setBody(trim($this->string(Body::getNameForValidation())->toString()))
            ->setMentions($mentions === [] ? null : $mentions));

        return back();
    }
}
