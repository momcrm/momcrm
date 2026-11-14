<?php

declare(strict_types=1);

namespace App\Attachment\Controllers;

use App\Attachment\Actions\CreateAttachment;
use App\Attachment\Actions\FindAttachment;
use App\Attachment\Exceptions\CouldNotFindAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Downloads a file. Only members of its team can.
 */
final class ShowAttachmentController
{
    /**
     * @throws CouldNotFindAttachment
     */
    public function __invoke(Request $request, string $attachment): StreamedResponse
    {
        $found = FindAttachment::visibleByIdForTeam($attachment, $request->user()?->current_team_id, (string) $request->user()?->getAuthIdentifier());

        if ($found === null || ! Storage::disk(CreateAttachment::DISK)->exists($found->path)) {
            throw CouldNotFindAttachment::notFound();
        }

        return Storage::disk(CreateAttachment::DISK)->download($found->path, $found->name);
    }
}
