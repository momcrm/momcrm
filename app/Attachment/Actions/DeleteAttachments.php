<?php

declare(strict_types=1);

namespace App\Attachment\Actions;

use App\Attachment\AttachmentEloquentModel;
use App\Shared\MorphMapKeyEnum;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes a ticket's or project's files and their stored copies, before the
 * record itself is deleted for good.
 */
final class DeleteAttachments
{
    public static function for(MorphMapKeyEnum $type, string $id): void
    {
        FindAttachment::forAttachable($type, $id)->each(function (AttachmentEloquentModel $attachment): void {
            Storage::disk(CreateAttachment::DISK)->delete($attachment->path);
            $attachment->delete();
        });
    }
}
