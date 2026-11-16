<?php

declare(strict_types=1);

namespace App\Folder\FormRequests;

use App\Attachment\Attachment;
use App\Folder\Actions\DeleteFolder;
use App\Folder\Actions\FindFolder;
use App\Folder\Exceptions\CouldNotFindFolder;
use App\Folder\Folder;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Deletes a folder and the folders inside it; their files move to the top level.
 */
final class DeleteFolderFormRequest extends FormRequest
{
    public Folder $folder;

    public function __construct()
    {
        parent::__construct();

        $this->folder = Folder::new();
    }

    /**
     * @throws CouldNotFindFolder
     */
    public function authorize(): Response
    {
        $this->folder = FindFolder::byIdForTeam((string) $this->route('folder'), $this->user()?->current_team_id);

        if ($this->folder->isNull()) {
            throw CouldNotFindFolder::notFound();
        }

        return Gate::authorize('create', Attachment::class);
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
        DeleteFolder::fromFolder($this->folder);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name deleted. Its files moved to the top level.', ['name' => $this->folder->getName()->toString()])]);

        return to_route('customers.files', $this->folder->getCustomerId()->toString());
    }
}
