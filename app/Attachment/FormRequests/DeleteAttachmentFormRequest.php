<?php

declare(strict_types=1);

namespace App\Attachment\FormRequests;

use App\Attachment\Actions\CreateAttachment;
use App\Attachment\Actions\FindAttachment;
use App\Attachment\AttachmentEloquentModel;
use App\Attachment\Exceptions\CouldNotFindAttachment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Removes a file and its stored copy.
 */
final class DeleteAttachmentFormRequest extends FormRequest
{
    public ?AttachmentEloquentModel $attachment = null;

    /**
     * @throws CouldNotFindAttachment
     */
    public function authorize(): bool
    {
        $this->attachment = FindAttachment::visibleByIdForTeam((string) $this->route('attachment'), $this->user()?->current_team_id, (string) $this->user()?->getAuthIdentifier());

        if ($this->attachment === null) {
            throw CouldNotFindAttachment::notFound();
        }

        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }

    public function process(): RedirectResponse
    {
        if ($this->attachment !== null) {
            Storage::disk(CreateAttachment::DISK)->delete($this->attachment->path);
            $this->attachment->delete();
        }

        return back();
    }
}
