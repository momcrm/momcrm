<?php

declare(strict_types=1);

namespace App\Conversation\FormRequests;

use App\Conversation\Actions\StartConversation;
use App\Message\Properties\Body;
use App\Shared\Table;
use App\Shared\Types;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

final class StartConversationFormRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'recipients' => ['required', 'array', 'min:1', 'max:20'],
            'recipients.*' => [
                'uuid',
                Rule::notIn([(string) $this->user()?->id]),
                Rule::exists(Table::TeamUser->value, 'user_id')->where('team_id', $this->user()?->current_team_id),
            ],
            Body::getNameForValidation() => Body::getValidationRulesForCreate($this),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'recipients.required' => __('Choose who to send it to.'),
            'recipients.*.not_in' => __('Choose someone other than yourself.'),
            'recipients.*.exists' => __('You can only message people in your team.'),
            Body::getNameForValidation().'.required' => __('Write a message first.'),
        ];
    }

    public function process(): RedirectResponse
    {
        $conversation = StartConversation::from(
            teamId: (string) $this->user()?->current_team_id,
            senderId: (string) $this->user()?->id,
            recipientIds: array_values(array_unique(Types::stringList($this->validated()['recipients']))),
            body: trim($this->string(Body::getNameForValidation())->toString()),
        );

        return to_route('messages.index', ['conversation' => $conversation->getId()->toString()]);
    }
}
