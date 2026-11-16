<?php

declare(strict_types=1);

namespace App\Attachment\Actions;

use App\Attachment\Attachment;
use App\Attachment\AttachmentCollection;
use App\Attachment\AttachmentEloquentModel;
use App\Attachment\AttachmentRelationship;
use App\Attachment\Properties\AttachableId;
use App\Attachment\Properties\AttachableType;
use App\Attachment\Properties\FolderId;
use App\Attachment\Properties\Id;
use App\Attachment\Properties\Name;
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
     * The files of several records of one kind, newest first, grouped by the
     * record's id, for lists that show each record's files.
     *
     * @param  array<int, string>  $ids
     * @return array<string, AttachmentCollection>
     */
    public static function forAttachables(MorphMapKeyEnum $type, array $ids): array
    {
        $grouped = [];

        AttachmentEloquentModel::query()
            ->withoutGlobalScopes()
            ->with(AttachmentRelationship::Uploader->value)
            ->where(AttachableType::getDatabaseTableColumnName(), $type->value)
            ->whereIn(AttachableId::getDatabaseTableColumnName(), $ids)
            ->latest()
            ->get()
            ->each(function (AttachmentEloquentModel $model) use (&$grouped): void {
                $grouped[$model->attachable_id][] = $model;
            });

        return array_map(fn (array $models): AttachmentCollection => Attachment::collect($models), $grouped);
    }

    /**
     * A customer's files in one folder, or at the top level for none, by name.
     */
    public static function inCustomerFolder(string $customerId, ?string $folderId): AttachmentCollection
    {
        return Attachment::collect(AttachmentEloquentModel::query()
            ->withoutGlobalScopes()
            ->with(AttachmentRelationship::Uploader->value)
            ->where(AttachableType::getDatabaseTableColumnName(), MorphMapKeyEnum::Customer->value)
            ->where(AttachableId::getDatabaseTableColumnName(), $customerId)
            ->where(FolderId::getDatabaseTableColumnName(), $folderId)
            ->orderBy(Name::getDatabaseTableColumnName())
            ->get()
            ->all());
    }

    /**
     * How many files a customer has, in any folder.
     */
    public static function countForCustomer(string $customerId): int
    {
        return AttachmentEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(AttachableType::getDatabaseTableColumnName(), MorphMapKeyEnum::Customer->value)
            ->where(AttachableId::getDatabaseTableColumnName(), $customerId)
            ->count();
    }

    /**
     * @return array{id: string, name: string, description: ?string, folder_id: ?string, size: int, mime_type: string, uploaded_by: ?string, created_at: ?string, url: string}
     */
    public static function listItem(Attachment $attachment): array
    {
        return [
            'id' => $attachment->getId()->toString(),
            'name' => $attachment->getName()->toString(),
            'description' => $attachment->getDescription()->toNullableString(),
            'folder_id' => $attachment->getFolderId()->toNullableString(),
            'size' => $attachment->getSize()->toInteger(),
            'mime_type' => $attachment->getMimeType()->toString(),
            'uploaded_by' => $attachment->getUploader()->toNullableData()?->getName()->toString(),
            'created_at' => $attachment->getCreatedAt()->toNullableISOString(),
            'url' => route('attachments.show', $attachment->getId()->toString()),
        ];
    }
}
