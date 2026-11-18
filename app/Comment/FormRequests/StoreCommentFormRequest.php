<?php

declare(strict_types=1);

namespace App\Comment\FormRequests;

use App\Comment\Actions\CreateComment;
use App\Comment\Comment;
use App\Comment\CommentVisibilityEnum;
use App\Comment\Properties\Body;
use App\Comment\Properties\Mentions;
use App\Comment\Properties\Visibility;
use App\MailboxMessage\Actions\QueueCommentEmail;
use App\MailboxMessage\TicketReplyRoute;
use App\Shared\Table;
use App\Shared\Types;
use App\Ticket\Actions\FindTicket;
use App\Ticket\Exceptions\CouldNotFindTicket;
use App\Ticket\Ticket;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            Visibility::getNameForValidation() => Visibility::getValidationRulesForCreate($this),
        ];
    }

    /**
     * A reply to the customer needs someone to send it to and a mailbox to
     * send it from.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || ! $this->isReply()) {
                return;
            }

            $route = TicketReplyRoute::forTicket($this->ticket);

            if ($route->to === null) {
                $validator->errors()->add(Visibility::getNameForValidation(), __('This ticket has no email address to reply to.'));
            } elseif ($route->mailbox->isNull()) {
                $validator->errors()->add(Visibility::getNameForValidation(), __('Connect a mailbox in Settings → Email before replying to customers.'));
            }
        }];
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

    /**
     * Saves the comment. A reply to the customer is also lined up to be
     * emailed, in the same step, so there is never one without the other.
     */
    public function process(): RedirectResponse
    {
        $mentions = array_values(array_unique(Types::stringList($this->validated()[Mentions::getNameForValidation()] ?? [])));

        DB::transaction(function () use ($mentions): void {
            $comment = CreateComment::fromComment(Comment::new()
                ->setTeamId($this->ticket->getTeamId())
                ->setTicketId($this->ticket->getId())
                ->setUserId((string) $this->user()?->id)
                ->setBody(trim($this->string(Body::getNameForValidation())->toString()))
                ->setMentions($mentions === [] ? null : $mentions)
                ->setVisibility($this->isReply() ? CommentVisibilityEnum::Public : CommentVisibilityEnum::Internal));

            if ($this->isReply()) {
                QueueCommentEmail::forComment($comment, TicketReplyRoute::forTicket($this->ticket));
            }
        });

        return back();
    }

    private function isReply(): bool
    {
        return $this->input(Visibility::getNameForValidation()) === CommentVisibilityEnum::Public->value;
    }
}
