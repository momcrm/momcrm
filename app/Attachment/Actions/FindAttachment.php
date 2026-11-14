<?php

declare(strict_types=1);

namespace App\Attachment\Actions;

use App\Attachment\AttachmentEloquentModel;
use App\Attachment\AttachmentRelationship;
use App\Attachment\Properties\AttachableId;
use App\Attachment\Properties\AttachableType;
use App\Attachment\Properties\Id;
use App\Attachment\Properties\TeamId;
use App\Project\Actions\FindProject;
use App\Shared\MorphMapKeyEnum;
use Illuminate\Database\Eloquent\Collection;

final class FindAttachment
{
    /**
     * An attachment in the team, unless it sits on a project the person may not see.
     */
    public static function visibleByIdForTeam(?string $id, ?string $teamId, string $userId): ?AttachmentEloquentModel
    {
        if ($id === null || $teamId === null) {
            return null;
        }

        $attachment = AttachmentEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->where(Id::getDatabaseTableColumnName(), $id)
            ->first();

        if (
            $attachment?->attachable_type === MorphMapKeyEnum::Project->value
            && FindProject::visibleByIdForTeam($attachment->attachable_id, $teamId, $userId)->isNull()
        ) {
            return null;
        }

        return $attachment;
    }

    /**
     * A ticket's or project's files, newest first, with who uploaded them.
     *
     * @return Collection<int, AttachmentEloquentModel>
     */
    public static function forAttachable(MorphMapKeyEnum $type, string $id): Collection
    {
        return AttachmentEloquentModel::query()
            ->withoutGlobalScopes()
            ->with(AttachmentRelationship::Uploader->value)
            ->where(AttachableType::getDatabaseTableColumnName(), $type->value)
            ->where(AttachableId::getDatabaseTableColumnName(), $id)
            ->latest()
            ->get();
    }

    /**
     * @return array{id: string, name: string, size: int, mime_type: string, uploaded_by: ?string, created_at: ?string, url: string}
     */
    public static function listItem(AttachmentEloquentModel $attachment): array
    {
        return [
            'id' => $attachment->id,
            'name' => $attachment->name,
            'size' => $attachment->size,
            'mime_type' => $attachment->mime_type,
            'uploaded_by' => $attachment->uploader?->name,
            'created_at' => $attachment->created_at?->toIso8601String(),
            'url' => route('attachments.show', $attachment->id),
        ];
    }
}
