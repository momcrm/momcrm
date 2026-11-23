<?php

declare(strict_types=1);

namespace App\Inbox;

use App\Project\ProjectEloquentModel;
use App\Record\RecordEloquentModel;
use App\Shift\ShiftEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketRelationship;
use App\User\UserEloquentModel;

/**
 * The data every in-app notification stores, so the inbox can show and link
 * it without loading anything else.
 */
final class InboxPayload
{
    /**
     * @return array{kind: string, team_id: string, actor_id: ?string, actor_name: ?string, subject_type: string, subject_id: string, number: int, title: string, project_name: ?string, message: string, excerpt: ?string}
     */
    public static function forTicket(InboxKindEnum $kind, TicketEloquentModel $ticket, ?UserEloquentModel $actor, string $message, ?string $excerpt = null, ?string $actorName = null): array
    {
        $project = $ticket->getRelationValue(TicketRelationship::Project->value);

        return [
            'kind' => $kind->value,
            'team_id' => $ticket->team_id,
            'actor_id' => $actor?->id,
            'actor_name' => $actor instanceof UserEloquentModel ? $actor->name : $actorName,
            'subject_type' => 'ticket',
            'subject_id' => $ticket->id,
            'number' => $ticket->number,
            'title' => $ticket->title,
            'project_name' => $project instanceof ProjectEloquentModel ? $project->name : null,
            'message' => $message,
            'excerpt' => $excerpt,
        ];
    }

    /**
     * A notification about a custom object's record. The object's name
     * stands where a ticket's project would, and its id is kept for the link.
     *
     * @return array{kind: string, team_id: string, actor_id: ?string, actor_name: ?string, subject_type: string, subject_id: string, object_id: string, number: int, title: string, project_name: ?string, message: string, excerpt: ?string}
     */
    public static function forRecord(InboxKindEnum $kind, RecordEloquentModel $record, string $objectName, ?UserEloquentModel $actor, string $message, ?string $excerpt = null): array
    {
        return [
            'kind' => $kind->value,
            'team_id' => $record->team_id,
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'subject_type' => 'record',
            'subject_id' => $record->id,
            'object_id' => $record->object_id,
            'number' => $record->number,
            'title' => $record->name,
            'project_name' => $objectName,
            'message' => $message,
            'excerpt' => $excerpt,
        ];
    }

    /**
     * @return array{kind: string, team_id: string, actor_id: ?string, actor_name: ?string, subject_type: string, subject_id: string, number: int, title: string, project_name: ?string, message: string, excerpt: ?string}
     */
    public static function forProject(InboxKindEnum $kind, ProjectEloquentModel $project, ?UserEloquentModel $actor, string $message): array
    {
        return [
            'kind' => $kind->value,
            'team_id' => $project->team_id,
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'subject_type' => 'project',
            'subject_id' => $project->id,
            'number' => $project->number,
            'title' => $project->name,
            'project_name' => $project->name,
            'message' => $message,
            'excerpt' => null,
        ];
    }

    /**
     * A notification about the member's own shift, which links to their time.
     *
     * @return array{kind: string, team_id: string, actor_id: ?string, actor_name: ?string, subject_type: string, subject_id: string, number: int, title: string, project_name: ?string, message: string, excerpt: ?string}
     */
    public static function forShift(InboxKindEnum $kind, ShiftEloquentModel $shift, string $title, string $message): array
    {
        return [
            'kind' => $kind->value,
            'team_id' => $shift->team_id,
            'actor_id' => null,
            'actor_name' => null,
            'subject_type' => 'shift',
            'subject_id' => $shift->id,
            'number' => 0,
            'title' => $title,
            'project_name' => null,
            'message' => $message,
            'excerpt' => null,
        ];
    }
}
