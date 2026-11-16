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
 * Downloads a file. Only members of its team can. It is always sent as a
 * download, never shown inline, and the browser is told not to guess its type.
 */
final class ShowAttachmentController
{
    /**
     * @throws CouldNotFindAttachment
     */
    public function __invoke(Request $request, string $attachment): StreamedResponse
    {
        $found = FindAttachment::visibleByIdForTeam($attachment, $request->user()?->current_team_id, (string) $request->user()?->id);

        $path = $found->getPath()->toString();

        if ($found->isNull() || ! Storage::disk(CreateAttachment::DISK)->exists($path)) {
            throw CouldNotFindAttachment::notFound();
        }

        $response = Storage::disk(CreateAttachment::DISK)->download($path, $found->getName()->toString());
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
