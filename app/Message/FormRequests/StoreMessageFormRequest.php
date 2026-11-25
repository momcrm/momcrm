<?php

declare(strict_types=1);

namespace App\Message\FormRequests;

use App\Conversation\Actions\FindConversation;
use App\Conversation\Conversation;
use App\Conversation\Exceptions\CouldNotFindConversation;
use App\Message\Actions\CreateMessage;
use App\Message\Message;
use App\Message\Properties\Body;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreMessageFormRequest extends FormRequest
{
    public Conversation $conversation;

    public function __construct()
    {
        parent::__construct();

        $this->conversation = Conversation::new();
    }

    /**
     * Only someone in the conversation can write in it.
     *
     * @throws CouldNotFindConversation
     */
    public function authorize(): bool
    {
        $this->conversation = FindConversation::byIdForParticipant((string) $this->route('conversation'), $this->user()?->current_team_id, (string) $this->user()?->id);

        if ($this->conversation->isNull()) {
            throw CouldNotFindConversation::notFound();
        }

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
            Body::getNameForValidation().'.required' => __('Write a message first.'),
        ];
    }

    public function process(): RedirectResponse
    {
        CreateMessage::fromMessage(Message::new()
            ->setTeamId($this->conversation->getTeamId())
            ->setConversationId($this->conversation->getId())
            ->setUserId((string) $this->user()?->id)
            ->setBody(trim($this->string(Body::getNameForValidation())->toString())));

        return to_route('messages.index', ['conversation' => $this->conversation->getId()->toString()]);
    }
}
