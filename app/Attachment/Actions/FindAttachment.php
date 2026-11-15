<?php

declare(strict_types=1);

namespace App\Attachment\Actions;

use App\Attachment\Attachment;
use App\Attachment\AttachmentCollection;
use App\Attachment\AttachmentEloquentModel;
use App\Attachment\AttachmentRelationship;
use App\Attachment\Properties\AttachableId;
use App\Attachment\Properties\AttachableType;
use App\Attachment\Properties\Id;
use App\Attachment\Properties\TeamId;
use App\Project\Actions\FindProject;
use App\Project\ProjectVisibility;
use App\Shared\MorphMapKeyEnum;
use App\Shared\Table;
use App\Ticket\Properties\Id as TicketId;
use App\Ticket\Properties\TeamId as TicketTeamId;
use App\Ticket\TicketEloquentModel;

final class FindAttachment
{
    /**
     * An attachment in the team, unless it sits on a project or ticket the person
     * may not see.
     */
    public static function visibleByIdForTeam(?string $id, ?string $teamId, string $userId): Attachment
    {
        if ($id === null || $teamId === null) {
            return Attachment::new();
        }

        $attachment = AttachmentEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->where(Id::getDatabaseTableColumnName(), $id)
            ->first();

        if ($attachment === null) {
            return Attachment::new();
        }

        $isVisible = match ($attachment->attachable_type) {
            MorphMapKeyEnum::Project->value => FindProject::visibleByIdForTeam($attachment->attachable_id, $teamId, $userId)->isNotNull(),
            MorphMapKeyEnum::Ticket->value => self::ticketIsVisible($attachment->attachable_id, $teamId, $userId),
            default => true,
        };

        return $isVisible ? Attachment::fromEloquentModel($attachment) : Attachment::new();
    }

    /**
     * Whether someone may see the ticket, whoever is signed in: queued and
     * console code has no user for VisibleTicketScope to read.
     */
    private static function ticketIsVisible(string $ticketId, string $teamId, string $userId): bool
    {
        $tickets = TicketEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TicketTeamId::getDatabaseTableColumnName(), $teamId)
            ->where(TicketId::getDatabaseTableColumnName(), $ticketId);

        ProjectVisibility::constrainTickets($tickets->getQuery(), $userId, Table::Tickets->value);

        return $tickets->exists();
    }

    /**
     * A ticket's or project's files, newest first, with who uploaded them.
     */
    public static function forAttachable(MorphMapKeyEnum $type, string $id): AttachmentCollection
    {
        return Attachment::collect(AttachmentEloquentModel::query()
            ->withoutGlobalScopes()
            ->with(AttachmentRelationship::Uploader->value)
            ->where(AttachableType::getDatabaseTableColumnName(), $type->value)
            ->where(AttachableId::getDatabaseTableColumnName(), $id)
            ->latest()
            ->get()
            ->all());
    }

    /**
     * @return array{id: string, name: string, size: int, mime_type: string, uploaded_by: ?string, created_at: ?string, url: string}
     */
    public static function listItem(Attachment $attachment): array
    {
        return [
            'id' => $attachment->getId()->toString(),
            'name' => $attachment->getName()->toString(),
            'size' => $attachment->getSize()->toInteger(),
            'mime_type' => $attachment->getMimeType()->toString(),
            'uploaded_by' => $attachment->getUploader()->toNullableData()?->getName()->toString(),
            'created_at' => $attachment->getCreatedAt()->toNullableISOString(),
            'url' => route('attachments.show', $attachment->getId()->toString()),
        ];
    }
}
