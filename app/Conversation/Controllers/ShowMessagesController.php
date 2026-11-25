<?php

declare(strict_types=1);

namespace App\Conversation\Controllers;

use App\Conversation\Actions\FindConversation;
use App\ConversationParticipant\Actions\MarkConversationRead;
use App\Message\Actions\FindMessage;
use App\TeamUser\Actions\FindTeamMembers;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The inbox's Messages tab: the person's conversations on the left, and the
 * one they opened (`?conversation=`) on the right, which counts as reading it.
 */
final class ShowMessagesController
{
    public function __invoke(Request $request): Response
    {
        /** @var UserEloquentModel $user */
        $user = $request->user();
        $teamId = (string) $user->current_team_id;
        $conversation = FindConversation::byIdForParticipant($request->string('conversation')->toString() ?: null, $teamId, $user->id);

        if ($conversation->isNotNull()) {
            MarkConversationRead::forParticipant($conversation->getId()->toString(), $user->id);
        }

        return Inertia::render('messages', [
            'conversations' => FindConversation::listForUser($teamId, $user->id),
            'selected' => $conversation->isNull() ? null : [
                'id' => $conversation->getId()->toString(),
                'participants' => FindConversation::othersIn($conversation->getId()->toString(), $user->id),
                'messages' => FindMessage::listForConversation($conversation->getId()->toString()),
            ],
            'members' => FindTeamMembers::optionsForTeam($teamId)
                ->reject(fn (array $member): bool => $member['id'] === $user->id)
                ->values(),
        ]);
    }
}
