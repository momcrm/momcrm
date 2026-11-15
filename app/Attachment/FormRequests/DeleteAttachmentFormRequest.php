<?php

declare(strict_types=1);

namespace App\Attachment\FormRequests;

use App\Attachment\Actions\DeleteAttachment;
use App\Attachment\Actions\FindAttachment;
use App\Attachment\Attachment;
use App\Attachment\Exceptions\CouldNotFindAttachment;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Removes a file and its stored copy.
 */
final class DeleteAttachmentFormRequest extends FormRequest
{
    public Attachment $attachment;

    public function __construct()
    {
        parent::__construct();

        $this->attachment = Attachment::new();
    }

    /**
     * @throws CouldNotFindAttachment
     */
    public function authorize(): Response
    {
        $this->attachment = FindAttachment::visibleByIdForTeam((string) $this->route('attachment'), $this->user()?->current_team_id, (string) $this->user()?->id);

        if ($this->attachment->isNull()) {
            throw CouldNotFindAttachment::notFound();
        }

        return Gate::authorize('delete', $this->attachment);
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
        DeleteAttachment::fromAttachment($this->attachment);

        return back();
    }
}
