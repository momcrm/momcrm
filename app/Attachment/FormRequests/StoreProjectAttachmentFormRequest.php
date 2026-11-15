<?php

declare(strict_types=1);

namespace App\Attachment\FormRequests;

use App\Attachment\Actions\CreateAttachment;
use App\Attachment\Attachment;
use App\Attachment\AttachmentValidationRules;
use App\Project\Actions\FindProject;
use App\Project\Exceptions\CouldNotFindProject;
use App\Project\ProjectEloquentModel;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

final class StoreProjectAttachmentFormRequest extends FormRequest
{
    public ?ProjectEloquentModel $project = null;

    /**
     * @throws CouldNotFindProject
     */
    public function authorize(): Response
    {
        $project = FindProject::visibleByIdForTeam((string) $this->route('project'), $this->user()?->current_team_id, (string) $this->user()?->id);

        if ($project->isNull()) {
            throw CouldNotFindProject::notFound();
        }

        $this->project = ProjectEloquentModel::query()->withoutGlobalScopes()->find($project->getId()->toString());

        return Gate::authorize('create', Attachment::class);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return AttachmentValidationRules::rules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return AttachmentValidationRules::messages();
    }

    public function process(): RedirectResponse
    {
        /** @var array<int, UploadedFile> $files */
        $files = $this->file(AttachmentValidationRules::FILES, []);

        foreach ($files as $file) {
            CreateAttachment::for($file, $this->project ?? new ProjectEloquentModel, (string) $this->user()?->id);
        }

        return back();
    }
}
